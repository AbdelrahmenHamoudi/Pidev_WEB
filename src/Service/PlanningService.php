<?php

namespace App\Service;

use App\Entity\Activite;
use App\Entity\Planning;
use App\Repository\PlanningRepository;
use Doctrine\ORM\EntityManagerInterface;

class PlanningService
{
    public function __construct(
        private EntityManagerInterface $em,
        private PlanningRepository     $repository,
    ) {}

    // ── CRUD ──────────────────────────────────────────────────────────────

    public function findById(int $id): ?Planning
    {
        return $this->repository->find($id);
    }

    public function findDisponiblesByActivite(int $idActivite): array
    {
        return $this->repository->findDisponiblesByActivite($idActivite);
    }

    public function create(Planning $planning): Planning
    {
        // Initialiser les places restantes = capacite max de l'activite
        if ($planning->getNbPlacesRestantes() === 0 && $planning->getActivite()) {
            $planning->setNbPlacesRestantes($planning->getActivite()->getCapaciteMax());
        }

        $this->em->persist($planning);
        $this->em->flush();
        return $planning;
    }

    public function update(Planning $planning): Planning
    {
        $this->em->flush();
        return $planning;
    }

    public function delete(Planning $planning): void
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

        $planning->reserverPlace();  // decrement + update etat si complet
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

        // Remettre disponible si etait complet
        if ($planning->getEtat() === 'Complet') {
            $planning->setEtat('Disponible');
        }

        $this->em->flush();
        return true;
    }

    /**
     * Retourne les dates distinctes disponibles pour une activite
     * (utilisee pour le filtre de dates)
     */
    public function getDatesDisponibles(int $idActivite): array
    {
        $plannings = $this->repository->findDisponiblesByActivite($idActivite);

        $dates = array_map(
            fn(Planning $p) => $p->getDatePlanning()->format('d/m/Y'),
            $plannings
        );

        return array_unique($dates);
    }
}
