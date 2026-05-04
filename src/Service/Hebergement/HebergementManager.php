<?php

namespace App\Service\Hebergement;

use App\Entity\Hebergement;
use InvalidArgumentException;

class HebergementManager
{
    public function validate(Hebergement $hebergement): bool
    {
        if (empty($hebergement->getTitre())) {
            throw new InvalidArgumentException('Le titre est obligatoire');
        }

        if (strlen($hebergement->getDesc_hebergement() ?? '') < 10) {
            throw new InvalidArgumentException('La description doit contenir au moins 10 caractères');
        }

        if ($hebergement->getPrixParNuitNumeric() <= 0) {
            throw new InvalidArgumentException('Le prix doit être supérieur à zéro');
        }

        $capacite = $hebergement->getCapacite();
        if ($capacite === null || $capacite <= 0) {
            throw new InvalidArgumentException('La capacité doit être supérieure à zéro');
        }

        return true;
    }
}
