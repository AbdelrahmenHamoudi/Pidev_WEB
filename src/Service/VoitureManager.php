<?php

namespace App\Service;

use App\Entity\Voiture;

class VoitureManager
{
    public function validate(Voiture $voiture): bool
    {
        if (empty($voiture->getMarque())) {
            throw new \InvalidArgumentException('La marque est obligatoire.');
        }

        if (empty($voiture->getImmatriculation())) {
            throw new \InvalidArgumentException("L'immatriculation est obligatoire.");
        }

        if ($voiture->getPrixKM() === null || $voiture->getPrixKM() <= 0) {
            throw new \InvalidArgumentException('Le prix par km doit être supérieur à 0.');
        }

        if ($voiture->getNbPlaces() < 1 || $voiture->getNbPlaces() > 9) {
            throw new \InvalidArgumentException('Le nombre de places doit être entre 1 et 9.');
        }

        return true;
    }
}
