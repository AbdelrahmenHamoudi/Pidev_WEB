<?php

namespace App\Service;

use App\Entity\Publication;

class PublicationManager
{
    private CensorshipService $censorshipService;

    public function __construct()
    {
        $this->censorshipService = new CensorshipService();
    }

    /**
     * Validate a publication according to business rules
     *
     * @param Publication $publication
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validate(Publication $publication): bool
    {
        $description = trim($publication->getDescriptionP());

        // Rule 1: Description must not be empty and must contain at least 5 characters
        if (empty($description) || mb_strlen($description) < 5) {
            throw new \InvalidArgumentException(
                'La description doit contenir au moins 5 caractères.'
            );
        }

        // Rule 2: Description must not contain forbidden words
        if ($this->censorshipService->containsForbiddenWords($description)) {
            $foundWords = $this->censorshipService->getForbiddenWordsFound($description);
            throw new \InvalidArgumentException(
                'La description contient des mots interdits : ' . implode(', ', $foundWords)
            );
        }

        return true;
    }
}