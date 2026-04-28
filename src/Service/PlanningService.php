<?php

namespace App\Service;

use App\Entity\Planningactivite;
use App\Repository\PlanningactiviteRepository;
use Doctrine\ORM\EntityManagerInterface;

class PlanningService
{
    public function __construct(
        private EntityManagerInterface     $em,
        private PlanningactiviteRepository $repository,
    ) {}

    // ══════════════════════════════════════════════════
    //  CRUD
    // ══════════════════════════════════════════════════

    public function findById(int $id): ?Planningactivite
    {
        return $this->repository->find($id);
    }

    public function findDisponiblesByActivite(int $idActivite): array
    {
        return $this->repository->findDisponiblesByActivite($idActivite);
    }

    public function findAllByActivite(int $idActivite): array
    {
        return $this->repository->findBy(['activite' => $idActivite], ['datePlanning' => 'ASC']);
    }

    public function create(Planningactivite $planning): Planningactivite
    {
        // Si pas de places définies, prendre la capacité max de l'activité
        if ($planning->getNbPlacesRestantes() === 0 && $planning->getActivite()) {
            $planning->setNbPlacesRestantes($planning->getActivite()->getCapaciteMax());
        }

        // Valider que heure_fin > heure_debut
        if ($planning->getHeureDebut() >= $planning->getHeureFin()) {
            throw new \InvalidArgumentException(
                "L'heure de fin doit être après l'heure de début"
            );
        }

        $this->em->persist($planning);
        $this->em->flush();
        return $planning;
    }

    public function update(Planningactivite $planning): Planningactivite
    {
        // Valider que heure_fin > heure_debut
        if ($planning->getHeureDebut() >= $planning->getHeureFin()) {
            throw new \InvalidArgumentException(
                "L'heure de fin doit être après l'heure de début"
            );
        }

        // Mettre à jour l'état selon les places restantes
        if ($planning->getNbPlacesRestantes() === 0) {
            $planning->setEtat('Complet');
        } elseif ($planning->getEtat() === 'Complet' && $planning->getNbPlacesRestantes() > 0) {
            $planning->setEtat('Disponible');
        }

        $this->em->flush();
        return $planning;
    }

    public function delete(Planningactivite $planning): void
    {
        $this->em->remove($planning);
        $this->em->flush();
    }

    // ══════════════════════════════════════════════════
    //  LOGIQUE DE RESERVATION
    // ══════════════════════════════════════════════════

    /**
     * Reserve une place dans un planning.
     *
     * Vérifie :
     * - Le planning existe
     * - Le planning est disponible (etat = Disponible)
     * - Il reste des places (nbPlacesRestantes > 0)
     * - La date n'est pas passée
     *
     * @return array{success: bool, message: string}
     */
    public function reserverPlace(int $idPlanning): array
    {
        $planning = $this->repository->find($idPlanning);

        // Vérification 1 : planning existe
        if (!$planning) {
            return ['success' => false, 'message' => 'Planning introuvable.'];
        }

        // Vérification 2 : date non passée
        $today = new \DateTime('today');
        if ($planning->getDatePlanning() < $today) {
            return ['success' => false, 'message' => 'Ce planning est déjà passé.'];
        }

        // Vérification 3 : état disponible
        if ($planning->getEtat() !== 'Disponible') {
            return ['success' => false, 'message' => 'Ce planning n\'est plus disponible.'];
        }

        // Vérification 4 : places restantes
        if ($planning->getNbPlacesRestantes() <= 0) {
            return ['success' => false, 'message' => 'Plus de places disponibles pour ce planning.'];
        }

        // Réservation — décrémentation atomique
        $planning->reserverPlace();
        $this->em->flush();

        return [
            'success' => true,
            'message' => 'Réservation confirmée ! Il reste ' .
                         $planning->getNbPlacesRestantes() . ' place(s).',
        ];
    }

    /**
     * Annule une réservation — remet une place disponible.
     *
     * @return array{success: bool, message: string}
     */
    public function annulerReservation(int $idPlanning): array
    {
        $planning = $this->repository->find($idPlanning);

        if (!$planning) {
            return ['success' => false, 'message' => 'Planning introuvable.'];
        }

        // Vérifier qu'on ne dépasse pas la capacité max
        $capaciteMax = $planning->getActivite()?->getCapaciteMax() ?? PHP_INT_MAX;
        if ($planning->getNbPlacesRestantes() >= $capaciteMax) {
            return ['success' => false, 'message' => 'Impossible d\'annuler : capacité maximale déjà atteinte.'];
        }

        $planning->libererPlace();
        $this->em->flush();

        return ['success' => true, 'message' => 'Réservation annulée avec succès.'];
    }

    // ══════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════

    /**
     * Retourne les dates distinctes disponibles pour une activité
     * Utilisé pour le filtre de dates dans la vue
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

    /**
     * Statistiques pour le dashboard admin
     */
    public function getStats(int $idActivite): array
    {
        $tous = $this->repository->findBy(['activite' => $idActivite]);

        $disponibles = array_filter($tous, fn($p) => $p->getEtat() === 'Disponible');
        $complets    = array_filter($tous, fn($p) => $p->getEtat() === 'Complet');
        $totalPlaces = array_sum(array_map(fn($p) => $p->getNbPlacesRestantes(), $disponibles));

        return [
            'total'       => count($tous),
            'disponibles' => count($disponibles),
            'complets'    => count($complets),
            'totalPlaces' => $totalPlaces,
        ];
    }
}