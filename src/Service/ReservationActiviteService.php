<?php

namespace App\Service;

use App\Entity\ReservationActivite;
use App\Entity\Planningactivite;
use App\Entity\Users;
use App\Repository\ReservationActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReservationActiviteService
{
    public function __construct(
        private EntityManagerInterface        $em,
        private ReservationActiviteRepository $repository,
        private PlanningService               $planningService,
    ) {}

    // ══════════════════════════════════════════════════
    //  CRÉER UNE RÉSERVATION
    // ══════════════════════════════════════════════════

    /**
     * Crée une réservation pour un utilisateur connecté.
     *
     * @return array{success: bool, message: string, reservation: ?ReservationActivite}
     */
    public function reserverPourUtilisateur(int $idPlanning, Users $utilisateur): array
    {
        $planning = $this->planningService->findById($idPlanning);

        // Vérification 1 : planning existe
        if (!$planning) {
            return ['success' => false, 'message' => 'Planning introuvable.', 'reservation' => null];
        }

        // Vérification 2 : déjà réservé
        if ($this->repository->existeDejaReservation($idPlanning, $utilisateur->getId())) {
            return ['success' => false, 'message' => 'Vous avez déjà réservé ce planning.', 'reservation' => null];
        }

        // Vérification 3 + décrémentation places
        $result = $this->planningService->reserverPlace($idPlanning);
        if (!$result['success']) {
            return ['success' => false, 'message' => $result['message'], 'reservation' => null];
        }

        // Créer la réservation
        $reservation = new ReservationActivite();
        $reservation->setPlanning($planning);
        $reservation->setUtilisateur($utilisateur);
        $reservation->setStatut('Confirmee');

        $this->em->persist($reservation);
        $this->em->flush();

        return [
            'success'     => true,
            'message'     => 'Réservation confirmée pour le ' .
                             $planning->getDatePlanning()->format('d/m/Y') . ' !',
            'reservation' => $reservation,
        ];
    }

    /**
     * Crée une réservation anonyme (sans compte utilisateur).
     *
     * @return array{success: bool, message: string, reservation: ?ReservationActivite}
     */
    public function reserverAnonyme(int $idPlanning, string $nom, string $email): array
    {
        $planning = $this->planningService->findById($idPlanning);

        if (!$planning) {
            return ['success' => false, 'message' => 'Planning introuvable.', 'reservation' => null];
        }

        $result = $this->planningService->reserverPlace($idPlanning);
        if (!$result['success']) {
            return ['success' => false, 'message' => $result['message'], 'reservation' => null];
        }

        $reservation = new ReservationActivite();
        $reservation->setPlanning($planning);
        $reservation->setNomClient($nom);
        $reservation->setEmailClient($email);
        $reservation->setStatut('Confirmee');

        $this->em->persist($reservation);
        $this->em->flush();

        return [
            'success'     => true,
            'message'     => 'Réservation confirmée ! Un email sera envoyé à ' . $email,
            'reservation' => $reservation,
        ];
    }

    // ══════════════════════════════════════════════════
    //  ANNULER UNE RÉSERVATION
    // ══════════════════════════════════════════════════

    /**
     * @return array{success: bool, message: string}
     */
    public function annuler(int $idReservation, ?Users $utilisateur = null): array
    {
        $reservation = $this->repository->find($idReservation);

        if (!$reservation) {
            return ['success' => false, 'message' => 'Réservation introuvable.'];
        }

        // Vérifier que c'est bien la réservation de cet utilisateur
        if ($utilisateur && $reservation->getUtilisateur()?->getId() !== $utilisateur->getId()) {
            return ['success' => false, 'message' => 'Vous ne pouvez pas annuler cette réservation.'];
        }

        if ($reservation->isAnnulee()) {
            return ['success' => false, 'message' => 'Cette réservation est déjà annulée.'];
        }

        // Remettre la place dans le planning
        $this->planningService->annulerReservation($reservation->getPlanning()->getId());

        $reservation->setStatut('Annulee');
        $this->em->flush();

        return ['success' => true, 'message' => 'Réservation annulée avec succès.'];
    }

    // ══════════════════════════════════════════════════
    //  LECTURE
    // ══════════════════════════════════════════════════

    public function findById(int $id): ?ReservationActivite
    {
        return $this->repository->find($id);
    }

    public function findByUtilisateur(Users $utilisateur): array
    {
        return $this->repository->findByUtilisateur($utilisateur->getId());
    }

    public function findByActivite(int $idActivite): array
    {
        return $this->repository->findByActivite($idActivite);
    }

    public function findByPlanning(int $idPlanning): array
    {
        return $this->repository->findByPlanning($idPlanning);
    }
}
