<?php
namespace App\Service;

use App\Entity\Activite;
use App\Entity\Planningactivite;

class ActiviteManager
{
    // ── Règles métier Activite ────────────────────────────────────────────

    public function validateActivite(Activite $activite): bool
    {
        // Règle 1 : nom obligatoire
        if (empty($activite->getNomA())) {
            throw new \InvalidArgumentException('Le nom de l\'activité est obligatoire');
        }

        // Règle 2 : prix positif
        if ($activite->getPrixParPersonne() <= 0) {
            throw new \InvalidArgumentException('Le prix doit être supérieur à zéro');
        }

        // Règle 3 : capacité >= 1
        if ($activite->getCapaciteMax() < 1) {
            throw new \InvalidArgumentException('La capacité doit être au moins 1');
        }

        return true;
    }

    // ── Règles métier Planning ────────────────────────────────────────────

    public function validatePlanning(Planningactivite $planning): bool
    {
        // Règle 4 : heure fin > heure debut
        if ($planning->getHeureDebut() >= $planning->getHeureFin()) {
            throw new \InvalidArgumentException(
                'L\'heure de fin doit être après l\'heure de début'
            );
        }

        // Règle 5 : places restantes >= 0
        if ($planning->getNbPlacesRestantes() < 0) {
            throw new \InvalidArgumentException(
                'Le nombre de places ne peut pas être négatif'
            );
        }

        return true;
    }
}