<?php

namespace App\Service;

use App\Entity\Planningactivite;
use App\Repository\PlanningactiviteRepository;
use Doctrine\ORM\EntityManagerInterface;

class PlanningService
{
    public function __construct(
        private EntityManagerInterface      $em,
        private PlanningactiviteRepository  $repository,
    ) {}

    // ── CRUD ──────────────────────────────────────────────────────────────

    public function findById(int $id): ?Planningactivite
    {
        return $this->repository->find($id);
    }

    public function findDisponiblesByActivite(int $idActivite): array
    {
        return $this->repository->findDisponiblesByActivite($idActivite);
    }

    public function create(Planningactivite $planning): Planningactivite
    {
        // Initialiser les places restantes = capacite max de l'activite
        if ($planning->getNbPlacesRestantes() === 0 && $planning->getActivite()) {
            $planning->setNbPlacesRestantes($planning->getActivite()->getCapaciteMax());
        }

        $this->em->persist($planning);
        $this->em->flush();
        return $planning;
    }

    public function update(Planningactivite $planning): Planningactivite
    {
        $this->em->flush();
        return $planning;
    }

    public function delete(Planningactivite $planning): void
    {
        $this->em->remove($planning);
        $this->em->flush();
    }

    // ── Metier : Reservation ──────────────────────────────────────────────

    /**
     * Reserve une place dans un planning.
     * Retourne true si succes, false si plus de places.
     */
    public function reserverPlace(int $idPlanning): bool
    {
        $planning = $this->repository->find($idPlanning);

        if (!$planning || !$planning->isDisponible()) {
            return false;
        }

        $planning->reserverPlace();
        $this->em->flush();

        return true;
    }

    /**
     * Annule une reservation (remet une place)
     */
    public function annulerReservation(int $idPlanning): bool
    {
        $planning = $this->repository->find($idPlanning);

        if (!$planning) return false;

        $planning->setNbPlacesRestantes($planning->getNbPlacesRestantes() + 1);

        if ($planning->getEtat() === 'Complet') {
            $planning->setEtat('Disponible');
        }

        $this->em->flush();
        return true;
    }

    /**
     * Retourne les dates distinctes disponibles pour une activite
     */
    public function getDatesDisponibles(int $idActivite): array
    {
        $plannings = $this->repository->findDisponiblesByActivite($idActivite);

        $dates = array_map(
            fn(Planningactivite $p) => $p->getDatePlanning()->format('d/m/Y'),
            $plannings
        );

        return array_unique($dates);
    }
}