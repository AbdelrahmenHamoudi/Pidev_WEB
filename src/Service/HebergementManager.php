<?php

namespace App\Service;

use App\Entity\Hebergement;
use InvalidArgumentException;

class HebergementManager
{
    public function validate(Hebergement $hebergement): bool
    {
        if (empty($hebergement->getTitre())) {
            throw new InvalidArgumentException('Le titre est obligatoire');
        }

        if ($hebergement->getPrixParNuitNumeric() <= 0) {
            throw new InvalidArgumentException('Le prix doit être supérieur à zéro');
        }

        if ($hebergement->getCapacite() <= 0) {
            throw new InvalidArgumentException('La capacité doit être supérieure à zéro');
        }

        return true;
    }
}
