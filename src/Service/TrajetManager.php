<?php

namespace App\Service;

use App\Entity\Trajet;

class TrajetManager
{
    public function validate(Trajet $trajet): bool
    {
        if (empty($trajet->getPointDepart())) {
            throw new \InvalidArgumentException('Le point de départ est obligatoire.');
        }

        if (empty($trajet->getPointArrivee())) {
            throw new \InvalidArgumentException("Le point d'arrivée est obligatoire.");
        }

        if ($trajet->getDistanceKm() === null || $trajet->getDistanceKm() <= 0) {
            throw new \InvalidArgumentException('La distance doit être supérieure à 0.');
        }

        if ($trajet->getNbPersonnes() < 1) {
            throw new \InvalidArgumentException('Le nombre de personnes doit être au moins 1.');
        }

        return true;
    }
}
