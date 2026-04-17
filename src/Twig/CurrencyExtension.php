<?php

namespace App\Twig;

use App\Service\Hebergement\CurrencyService;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CurrencyExtension extends AbstractExtension
{
    public function __construct(
        private CurrencyService $currencyService,
        private RequestStack $requestStack
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('format_price', [$this, 'formatPrice'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Converts and formats a price based on the user's currency preference.
     */
    public function formatPrice(float $amount, string $from = 'TND'): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) return number_format($amount, 2) . ' DT';

        $session = $request->getSession();
        $targetCurrency = $session->get('_currency', 'TND');
        
        // Convert
        $convertedAmount = $this->currencyService->convert($amount, $from, $targetCurrency);
        $symbol = $this->currencyService->getSymbol($targetCurrency);

        // Format
        if ($targetCurrency === 'TND') {
            return '<span class="price-value">' . number_format($convertedAmount, 0, '.', ' ') . '</span> <span class="currency-symbol">' . $symbol . '</span>';
        }

        return '<span class="price-value">' . number_format($convertedAmount, 2, '.', ' ') . '</span> <span class="currency-symbol">' . $symbol . '</span>';
    }
}
