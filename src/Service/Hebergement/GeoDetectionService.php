<?php

namespace App\Service\Hebergement;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeoDetectionService
{
    private const API_URL = 'http://ip-api.com/json/';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    /**
     * Detects user location and returns suggested locale and currency.
     */
    public function detectPreferences(string $ip): array
    {
        // For local development, simulate a country
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return ['locale' => 'fr', 'currency' => 'TND', 'country' => 'Tunisie'];
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL . $ip);
            $data = $response->toArray();
            
            $countryCode = $data['countryCode'] ?? 'TN';
            
            return $this->mapCountryToPreferences($countryCode, $data['country'] ?? 'Tunisie');
        } catch (\Exception $e) {
            return ['locale' => 'fr', 'currency' => 'TND', 'country' => 'Tunisie'];
        }
    }

    private function mapCountryToPreferences(string $code, string $name): array
    {
        $map = [
            'TN' => ['locale' => 'fr', 'currency' => 'TND'],
            'FR' => ['locale' => 'fr', 'currency' => 'EUR'],
            'US' => ['locale' => 'en', 'currency' => 'USD'],
            'GB' => ['locale' => 'en', 'currency' => 'GBP'],
            'MA' => ['locale' => 'fr', 'currency' => 'MAD'],
            'DZ' => ['locale' => 'fr', 'currency' => 'DZD'],
            'SA' => ['locale' => 'ar', 'currency' => 'SAR'],
            'AE' => ['locale' => 'ar', 'currency' => 'AED'],
            'QA' => ['locale' => 'ar', 'currency' => 'QAR'],
        ];

        $prefs = $map[$code] ?? ['locale' => 'en', 'currency' => 'EUR'];
        $prefs['country'] = $name;

        return $prefs;
    }
}
