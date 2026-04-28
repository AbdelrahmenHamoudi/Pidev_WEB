<?php

namespace App\Controller\Promotion;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReservationRepository $reservationRepository
    ) {}

    /**
     * List user reservations ("Mes Réservations")
     */
    #[Route('/mes-reservations', name: 'app_reservation_list', methods: ['GET'])]
    public function list(): Response
    {
        $user = $this->getUser();
        $reservations = $this->reservationRepository->findBy(['user' => $user], ['dateDebut' => 'ASC']);

        // Annotate each reservation with canModify flag
        $reservationsData = array_map(function (Reservation $r) {
            return [
                'entity'     => $r,
                'canModify'  => $this->isModifiable($r),
                'hoursLeft'  => $this->hoursUntilStart($r),
            ];
        }, $reservations);

        return $this->render('reservation/list.html.twig', [
            'reservationsData' => $reservationsData,
        ]);
    }

    /**
     * Returns reservation data as JSON for the modal (AJAX)
     */
    #[Route('/{id}/data', name: 'app_reservation_data', methods: ['GET'])]
    public function getData(Reservation $reservation): JsonResponse
    {
        // Security: only owner can see
        if ($reservation->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $canModify = $this->isModifiable($reservation);
        $promo = $reservation->getPromotion();
        $type = $promo ? strtolower($promo->getType()) : 'hebergement';

        $data = [
            'id'         => $reservation->getId(),
            'canModify'  => $canModify,
            'type'       => $type,
            'dateDebut'  => $reservation->getDateDebut()?->format('Y-m-d'),
            'dateFin'    => $reservation->getDateFin()?->format('Y-m-d'),
            'nbNuits'    => $reservation->getNbNuits(),
            'nbPersonnes'=> $reservation->getNbPersonnes(),
            'kilometrage'=> $reservation->getKilometrage(),
            'promoMin'   => $promo?->getDateDebut()?->format('Y-m-d'),
            'promoMax'   => $promo?->getDateFin()?->format('Y-m-d'),
            'promoNom'   => $promo?->getNom(),
        ];

        return $this->json($data);
    }

    /**
     * Handle reservation update (AJAX POST)
     */
    #[Route('/{id}/modifier', name: 'app_reservation_modifier', methods: ['POST'])]
    public function modifier(Reservation $reservation, Request $request): JsonResponse
    {
        // Security check
        if ($reservation->getUser() !== $this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        // 24h business rule
        if (!$this->isModifiable($reservation)) {
            return $this->json([
                'success' => false,
                'message' => 'Modification impossible : moins de 24h avant la réservation.',
            ], 400);
        }

        $data = json_decode($request->getContent(), true);
        $promo = $reservation->getPromotion();
        $type = $promo ? strtolower($promo->getType()) : 'hebergement';

        try {
            // Validate & apply fields based on promotion type
            switch ($type) {
                case 'hebergement':
                    $this->applyHebergement($reservation, $data, $promo);
                    break;
                case 'voiture':
                    $this->applyVoiture($reservation, $data, $promo);
                    break;
                case 'activite':
                case 'activité':
                    $this->applyActivite($reservation, $data, $promo);
                    break;
                default:
                    $this->applyHebergement($reservation, $data, $promo);
            }

            $this->em->persist($reservation);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'message' => '✅ Réservation mise à jour avec succès.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer.',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────

    private function isModifiable(Reservation $r): bool
    {
        $start = $r->getDateDebut();
        if (!$start) {
            return false;
        }
        $now = new \DateTime();
        $diff = $start->getTimestamp() - $now->getTimestamp();
        return $diff > 86400; // more than 24 hours
    }

    private function hoursUntilStart(Reservation $r): ?float
    {
        $start = $r->getDateDebut();
        if (!$start) {
            return null;
        }
        $now = new \DateTime();
        return round(($start->getTimestamp() - $now->getTimestamp()) / 3600, 1);
    }

    private function applyHebergement(Reservation $r, array $data, $promo): void
    {
        $dateDebut = $this->parseDate($data['dateDebut'] ?? null, 'Date de début');
        $nbNuits   = (int) ($data['nbNuits'] ?? 1);

        if ($nbNuits < 1) {
            throw new \InvalidArgumentException('Le nombre de nuits doit être au moins 1.');
        }

        $this->validateDateInPromo($dateDebut, $promo, 'Date de début');
        $dateFin = (clone $dateDebut)->modify("+{$nbNuits} days");
        $this->validateDateInPromo($dateFin, $promo, 'Date de fin calculée');

        $r->setDateDebut($dateDebut);
        $r->setDateFin($dateFin);
        $r->setNbNuits($nbNuits);
    }

    private function applyVoiture(Reservation $r, array $data, $promo): void
    {
        $dateDebut   = $this->parseDate($data['dateDebut'] ?? null, 'Date');
        $kilometrage = (float) ($data['kilometrage'] ?? 0);

        if ($kilometrage <= 0) {
            throw new \InvalidArgumentException('Le kilométrage doit être supérieur à 0.');
        }

        $this->validateDateInPromo($dateDebut, $promo, 'Date');

        $r->setDateDebut($dateDebut);
        $r->setKilometrage($kilometrage);
    }

    private function applyActivite(Reservation $r, array $data, $promo): void
    {
        $dateDebut   = $this->parseDate($data['dateDebut'] ?? null, 'Date');
        $nbPersonnes = (int) ($data['nbPersonnes'] ?? 1);

        if ($nbPersonnes < 1) {
            throw new \InvalidArgumentException('Le nombre de personnes doit être au moins 1.');
        }

        $this->validateDateInPromo($dateDebut, $promo, 'Date');

        $r->setDateDebut($dateDebut);
        $r->setNbPersonnes($nbPersonnes);
    }

    private function parseDate(?string $raw, string $label): \DateTime
    {
        if (!$raw) {
            throw new \InvalidArgumentException("{$label} est requis(e).");
        }
        $d = \DateTime::createFromFormat('Y-m-d', $raw);
        if (!$d) {
            throw new \InvalidArgumentException("{$label} est invalide.");
        }
        return $d;
    }

    private function validateDateInPromo(\DateTime $date, $promo, string $label): void
    {
        if (!$promo) {
            return;
        }
        $min = $promo->getDateDebut();
        $max = $promo->getDateFin();
        if ($min && $date < $min) {
            throw new \InvalidArgumentException("{$label} est avant le début de la promotion ({$min->format('d/m/Y')}).");
        }
        if ($max && $date > $max) {
            throw new \InvalidArgumentException("{$label} est après la fin de la promotion ({$max->format('d/m/Y')}).");
        }
    }
}