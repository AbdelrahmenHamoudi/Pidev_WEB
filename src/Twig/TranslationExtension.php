<?php

namespace App\Twig;

use App\Service\Hebergement\TranslationService;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TranslationExtension extends AbstractExtension
{
    public function __construct(
        private TranslationService $translationService,
        private RequestStack $requestStack
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('auto_translate', [$this, 'autoTranslate']),
        ];
    }

    /**
     * Automatically translates text based on the current request locale.
     */
    public function autoTranslate(string $text): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) return $text;

        $targetLocale = $request->getLocale();
        
        // If it's already in the target locale (assuming source is FR for descriptions)
        if ($targetLocale === 'fr') return $text;

        return $this->translationService->translate($text, $targetLocale, 'fr');
    }
}
