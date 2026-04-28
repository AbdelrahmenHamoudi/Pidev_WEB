<?php

namespace App\Service\Hebergement;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CurrencyService
{
    private const API_URL = 'https://api.frankfurter.app/latest';
    private const CACHE_EXPIRE = 3600; // 1h

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache
    ) {}

    /**
     * Gets exchange rates relative to EUR.
     */
    public function getRates(): array
    {
        return $this->cache->get('currency_rates', function (ItemInterface $item) {
            $item->expiresAfter(self::CACHE_EXPIRE);
            
            // Hardcoded fallbacks if API fails
            $fallbacks = [
                'USD' => 1.08,
                'TND' => 3.40,
                'GBP' => 0.86,
            ];

            try {
                $response = $this->httpClient->request('GET', self::API_URL, [
                    'query' => ['from' => 'EUR'],
                    'timeout' => 2 // Avoid long waiting
                ]);
                $data = $response->toArray();
                
                return array_merge($fallbacks, $data['rates'] ?? []);
            } catch (\Exception $e) {
                return $fallbacks;
            }
        });
    }

    /**
     * Converts an amount from one currency to another.
     */
    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) return $amount;

        $rates = $this->getRates();
        
        // Base everything on EUR
        $baseAmount = ($from === 'EUR') ? $amount : $amount / ($rates[$from] ?? 1);
        $finalAmount = ($to === 'EUR') ? $baseAmount : $baseAmount * ($rates[$to] ?? 1);

        return round($finalAmount, 2);
    }

    /**
     * Get symbol for a currency code.
     */
    public function getSymbol(string $currency): string
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'TND' => 'DT',
            'GBP' => '£'
        ];

        return $symbols[$currency] ?? $currency;
    }
}
