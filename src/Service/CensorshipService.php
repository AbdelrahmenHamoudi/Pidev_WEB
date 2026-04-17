<?php

namespace App\Service;

class CensorshipService
{
    private array $forbiddenWords = [
        'bhim',
        'kalb',
        'couchon',
        // Add variations if needed
        'BHIM',
        'KALB',
        'COUCHON',
        'Bhim',
        'Kalb',
        'Couchon',
    ];

    /**
     * Check if text contains any forbidden words
     */
    public function containsForbiddenWords(string $text): bool
    {
        $lowerText = strtolower($text);
        
        foreach (['bhim', 'kalb', 'couchon'] as $word) {
            if (str_contains($lowerText, $word)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get the forbidden words found in the text
     */
    public function getForbiddenWordsFound(string $text): array
    {
        $found = [];
        $lowerText = strtolower($text);
        
        foreach (['bhim', 'kalb', 'couchon'] as $word) {
            if (str_contains($lowerText, $word)) {
                $found[] = $word;
            }
        }
        
        return $found;
    }

    /**
     * Get list of forbidden words for display
     */
    public function getForbiddenWordsList(): array
    {
        return ['bhim', 'kalb', 'couchon'];
    }

    /**
     * Validate text and return error message if forbidden words found
     */
    public function validateText(string $text, string $fieldName = 'texte'): ?string
    {
        if ($this->containsForbiddenWords($text)) {
            $found = $this->getForbiddenWordsFound($text);
            $wordsList = implode(', ', array_unique($found));
            return sprintf('⚠️ Le %s contient des mots interdits : %s', $fieldName, $wordsList);
        }
        
        return null;
    }
}