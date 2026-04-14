<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Repository\TrajetRepository;
use App\Repository\VoitureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Service\PdfService;

#[Route('/trajets')]
final class TrajetController extends AbstractController
{
    private $pdfService;

    public function __construct(PdfService $pdfService) {
        $this->pdfService = $pdfService;
    }

    private const DEFAULT_USER_ID = 32;

    #[Route('', name: 'app_trajet', methods: ['GET'])]
    public function index(Request $request, TrajetRepository $trajetRepo, VoitureRepository $voitureRepo): Response
    {
        $user = $this->getUser();
        $userId = $user ? $user->getId() : self::DEFAULT_USER_ID;

        $search = trim($request->query->get('search', ''));
        $statut = trim($request->query->get('statut', ''));
        $voitureId = $request->query->get('voiture', '');
        $sortBy = $request->query->get('sort', 'date_reservation');
        $order = strtoupper($request->query->get('order', 'DESC'));

        $qb = $trajetRepo->createQueryBuilder('t')
            ->leftJoin('t.id_voiture', 'v')
            ->addSelect('v');

        if ($user) {
            $qb->andWhere('t.id_utilisateur = :userId')
               ->setParameter('userId', $user->getId());
        }

        if ($search) {
            $qb->andWhere('t.point_depart LIKE :search OR t.point_arrivee LIKE :search OR v.marque LIKE :search OR v.modele LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($statut) {
            $qb->andWhere('t.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($voitureId !== '' && $voitureId !== null) {
            $qb->andWhere('v.id = :voitureId')
                ->setParameter('voitureId', (int) $voitureId);
        }

        $sortableFields = ['date_reservation', 'point_depart', 'point_arrivee', 'nb_personnes', 'statut'];
        if (!in_array($sortBy, $sortableFields, true)) {
            $sortBy = 'date_reservation';
        }

        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $qb->orderBy('t.' . $sortBy, $order);
        $trajets = $qb->getQuery()->getResult();
        $voitures = $voitureRepo->findAll();

        return $this->render('frontend/trajet/index.html.twig', [
            'trajets' => $trajets,
            'voitures' => $voitures,
            'search' => $search,
            'statut' => $statut,
            'voitureId' => $voitureId,
            'sortBy' => $sortBy,
            'order' => $order,
        ]);
    }

    #[Route('/admin/export/pdf', name: 'app_trajet_export_pdf', methods: ['GET'])]
    public function exportPdf(TrajetRepository $trajetRepo): Response
    {
        $trajets = $trajetRepo->findAll();
        $html = $this->renderView('backend/pdf/trajets.html.twig', [
            'trajets' => $trajets
        ]);

        return $this->pdfService->showPdfFile($html, "Rapport_Trajets_" . date('d_m_Y'));
    }

    #[Route('/{id}/recu', name: 'app_trajet_receipt_pdf', methods: ['GET'])]
    public function exportReceipt(Trajet $trajet): Response
    {
        $html = $this->renderView('backend/pdf/receipt.html.twig', [
            'trajet' => $trajet
        ]);

        return $this->pdfService->showPdfFile($html, "Recu_Reservation_" . $trajet->getId());
    }

    #[Route('/ajouter', name: 'app_trajet_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em, VoitureRepository $voitureRepo, ValidatorInterface $validator, HubInterface $hub, MailerInterface $mailer): Response
    {
        $trajet = new Trajet();

        // Pre-select car if ID is provided in URL
        $preSelectedVoitureId = $request->query->get('voiture_id');
        if ($preSelectedVoitureId) {
            $preSelectedVoiture = $voitureRepo->find($preSelectedVoitureId);
            if ($preSelectedVoiture) {
                $trajet->setIdVoiture($preSelectedVoiture);
            }
        }

        if ($request->isMethod('POST')) {
            $voitureId = $request->request->get('id_voiture');
            $voiture = $voitureRepo->find($voitureId);

            if (!$voiture) {
                $this->addFlash('error', 'Veuillez choisir une voiture valide.');
                return $this->redirectToRoute('app_trajet_create');
            }

            $dateString = $request->request->get('date_reservation');
            $date = $dateString ? new \DateTime($dateString) : new \DateTime();

            // Availability Check
            $availableVoitures = $voitureRepo->findAvailableCarsForDay($date);
            $isAvailable = false;
            foreach ($availableVoitures as $av) {
                if ($av->getId_voiture() === $voiture->getId_voiture()) {
                    $isAvailable = true;
                    break;
                }
            }

            if (!$isAvailable) {
                $this->addFlash('error', 'Cette voiture est déjà réservée pour cette date.');
                return $this->redirectToRoute('app_trajet_create');
            }

            $trajet->setIdVoiture($voiture);

            $user = $this->getUser();
            $trajet->setIdUtilisateur($user ? $user->getId() : self::DEFAULT_USER_ID);

            $trajet->setPointDepart($request->request->get('point_depart'));
            $trajet->setPointArrivee($request->request->get('point_arrivee'));
            $trajet->setDistanceKm((float) $request->request->get('distance_km'));

            $dateString = $request->request->get('date_reservation');
            $trajet->setDateReservation($dateString ? new \DateTime($dateString) : null);

            $trajet->setNbPersonnes((int) $request->request->get('nb_personnes', 1));
            $trajet->setStatut($request->request->get('statut', 'Réservé'));

            $errors = $validator->validate($trajet);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $trajet->setStatut('Réservé');
                $em->persist($trajet);
                $em->flush();

                $update = new Update(
                    'https://re7la.com/trajets',
                    json_encode([
                        'action' => 'add_trajet',
                        'message' => "Nouveau trajet réservé : {$trajet->getPointDepart()} vers {$trajet->getPointArrivee()}",
                        'voiture_marque' => $voiture->getMarque(),
                        'voiture_modele' => $voiture->getModele()
                    ])
                );
                
                try {
                    $hub->publish($update);
                } catch (\Exception $e) {
                    // Ignore silently if Mercure is down
                }

                // --- SEND CONFIRMATION EMAIL ---
                try {
                    $email = (new TemplatedEmail())
                        ->from('dhiambarki001@gmail.com')
                        ->to('jeudihama@gmail.com')
                        ->subject('Confirmation de votre réservation RE7LA')
                        ->htmlTemplate('emails/reservation_confirmation.html.twig')
                        ->context([
                            'trajet' => $trajet,
                        ]);

                    $mailer->send($email);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Le trajet est réservé, mais l\'email de confirmation n\'a pas pu être envoyé.');
                }

                $this->addFlash('success', 'Trajet réservé ! Veuillez procéder au paiement (optionnel).');
                return $this->redirectToRoute('app_paiement_checkout', ['id' => $trajet->getId()]);
            }
        }

        // Default availability check for the form
        $dateStr = $request->request->get('date_reservation') ?: 'now';
        try {
            $date = new \DateTime($dateStr);
        } catch (\Exception $e) {
            $date = new \DateTime();
        }
        $voitures = $voitureRepo->findAvailableCarsForDay($date);

        // If a car was pre-selected but is NOT in the available list for 'now', 
        // we add it anyway so the user can see it selected (but validation will fail if they submit 'now')
        // Or better: ensure it's in the list so the dropdown shows it.
        $found = false;
        if ($trajet->getIdVoiture()) {
            foreach ($voitures as $v) {
                if ($v->getId_voiture() === $trajet->getIdVoiture()->getId_voiture()) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $voitures[] = $trajet->getIdVoiture();
            }
        }

        return $this->render('frontend/trajet/form.html.twig', [
            'trajet' => $trajet,
            'voitures' => $voitures,
            'title' => 'Réserver un trajet',
        ]);
    }

    #[Route('/{id}', name: 'app_trajet_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Trajet $trajet): Response
    {
        return $this->render('frontend/trajet/show.html.twig', [
            'trajet' => $trajet,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_trajet_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Trajet $trajet, EntityManagerInterface $em, VoitureRepository $voitureRepo, ValidatorInterface $validator, HubInterface $hub): Response
    {
        if ($request->isMethod('POST')) {
            $voitureId = $request->request->get('id_voiture');
            $voiture = $voitureRepo->find($voitureId);

            if ($voiture) {
                $dateString = $request->request->get('date_reservation');
                $date = $dateString ? new \DateTime($dateString) : $trajet->getDateReservation();

                // Availability Check
                $availableVoitures = $voitureRepo->findAvailableCarsForDay($date, $trajet->getId());
                $isAvailable = false;
                foreach ($availableVoitures as $av) {
                    if ($av->getId_voiture() === $voiture->getId_voiture()) {
                        $isAvailable = true;
                        break;
                    }
                }

                if (!$isAvailable) {
                    $this->addFlash('error', 'Cette voiture est déjà réservée pour cette date.');
                    return $this->redirectToRoute('app_trajet_edit', ['id' => $trajet->getId()]);
                }

                $trajet->setIdVoiture($voiture);
            }

            $trajet->setPointDepart($request->request->get('point_depart'));
            $trajet->setPointArrivee($request->request->get('point_arrivee'));
            $trajet->setDistanceKm((float) $request->request->get('distance_km'));

            $dateString = $request->request->get('date_reservation');
            $trajet->setDateReservation($dateString ? new \DateTime($dateString) : null);

            $trajet->setNbPersonnes((int) $request->request->get('nb_personnes', 1));
            $trajet->setStatut($request->request->get('statut', 'Réservé'));

            $errors = $validator->validate($trajet);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $em->flush();

                // Mercure Notification
                $update = new \Symfony\Component\Mercure\Update(
                    'https://re7la.com/trajets',
                    json_encode([
                        'action' => 'edit_trajet',
                        'message' => "Le trajet #{$trajet->getId()} ({$trajet->getPointDepart()} -> {$trajet->getPointArrivee()}) a été modifié.",
                        'details' => [
                            'id' => $trajet->getId(),
                            'depart' => $trajet->getPointDepart(),
                            'arrivee' => $trajet->getPointArrivee()
                        ]
                    ])
                );
                try {
                    $hub->publish($update);
                } catch (\Exception $e) {}

                $this->addFlash('success', 'Trajet modifié avec succès !');
                return $this->redirectToRoute('app_trajet');
            }
        }

        $voitures = $voitureRepo->findAvailableCarsForDay($trajet->getDateReservation(), $trajet->getId());

        return $this->render('frontend/trajet/form.html.twig', [
            'trajet' => $trajet,
            'voitures' => $voitures,
            'title' => 'Modifier le trajet',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_trajet_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Trajet $trajet, EntityManagerInterface $em, \Symfony\Component\Mercure\HubInterface $hub): Response
    {
        if ($this->isCsrfTokenValid('delete' . $trajet->getId(), $request->request->get('_csrf_token'))) {
            $id = $trajet->getId();
            $depart = $trajet->getPointDepart();
            $arrivee = $trajet->getPointArrivee();

            $em->remove($trajet);
            $em->flush();

            // Mercure Notification
            $update = new \Symfony\Component\Mercure\Update(
                'https://re7la.com/trajets',
                json_encode([
                    'action' => 'delete_trajet',
                    'message' => "Trajet #$id ($depart -> $arrivee) supprimé.",
                ])
            );
            try {
                $hub->publish($update);
            } catch (\Exception $e) {}

            $this->addFlash('success', 'Trajet supprimé avec succès !');
        }

        return $this->redirectToRoute('app_trajet');
    }

    #[Route('/api/available-cars', name: 'api_trajet_available_cars', methods: ['GET'])]
    public function getAvailableCars(Request $request, VoitureRepository $voitureRepo): Response
    {
        $dateStr = $request->query->get('date');
        $excludeId = $request->query->get('exclude');

        if (!$dateStr) {
            return $this->json(['error' => 'Date missing'], 400);
        }

        try {
            $date = new \DateTime($dateStr);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date'], 400);
        }

        $voitures = $voitureRepo->findAvailableCarsForDay($date, $excludeId ? (int)$excludeId : null);
        
        $data = [];
        foreach ($voitures as $v) {
            $data[] = [
                'id' => $v->getId_voiture(),
                'text' => $v->getMarque() . ' ' . $v->getModele() . ' — ' . $v->getImmatriculation()
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/brouillon/save', name: 'api_trajet_brouillon_save', methods: ['POST'])]
    public function saveDraft(Request $request, CacheInterface $cache): Response
    {
        $data = $request->request->all();
        if (empty($data)) {
            $data = json_decode($request->getContent(), true) ?: [];
        }

        // Generate a simple 6-char code
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $cacheKey = 'trajet_draft_' . $code;

        $cache->get($cacheKey, function (ItemInterface $item) use ($data) {
            $item->expiresAfter(86400); // 24 hours
            return $data;
        });

        return $this->json([
            'status' => 'success',
            'code' => $code,
            'message' => 'Brouillon enregistré avec succès !'
        ]);
    }

    #[Route('/api/brouillon/{code}', name: 'api_trajet_brouillon_load', methods: ['GET'])]
    public function loadDraft(string $code, CacheInterface $cache): Response
    {
        $cacheKey = 'trajet_draft_' . strtoupper($code);
        $data = $cache->get($cacheKey, function (ItemInterface $item) {
            return null;
        });

        if (!$data) {
            return $this->json(['status' => 'error', 'message' => 'Code de brouillon invalide ou expiré.'], 404);
        }

        return $this->json($data);
    }
}
