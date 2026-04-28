<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeteoService
{
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $client,
        string $openweatherKey
    ) {
        $this->apiKey = $openweatherKey;
    }

    // ── Géocodage : ville → coordonnées GPS ──────────────────────────────
    public function geocoder(string $ville): ?array
    {
        try {
            $response = $this->client->request('GET',
                'https://nominatim.openstreetmap.org/search', [
                    'query' => [
                        'q'      => $ville . ', Tunisie',
                        'format' => 'json',
                        'limit'  => 1,
                    ],
                    'headers' => [
                        'User-Agent' => 'RE7LA-App/1.0'
                    ]
                ]
            );

            $data = $response->toArray();

            if (empty($data)) return null;

            return [
                'lat' => (float) $data[0]['lat'],
                'lon' => (float) $data[0]['lon'],
                'nom' => $data[0]['display_name'],
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    // ── Météo actuelle par ville ──────────────────────────────────────────
    public function getMeteo(string $ville): ?array
    {
        try {
            $response = $this->client->request('GET',
                'https://api.openweathermap.org/data/2.5/weather', [
                    'query' => [
                        'q'     => $ville . ',TN',
                        'appid' => $this->apiKey,
                        'units' => 'metric',
                        'lang'  => 'fr',
                    ]
                ]
            );

            $data = $response->toArray();

            return [
                'ville'       => $data['name'],
                'temperature' => round($data['main']['temp']),
                'ressenti'    => round($data['main']['feels_like']),
                'humidite'    => $data['main']['humidity'],
                'description' => $data['weather'][0]['description'],
                'icone'       => $data['weather'][0]['icon'],
                'icone_url'   => 'https://openweathermap.org/img/wn/' 
                                  . $data['weather'][0]['icon'] . '@2x.png',
                'vent'        => round($data['wind']['speed'] * 3.6), // m/s → km/h
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    // ── Météo + GPS en un seul appel ─────────────────────────────────────
    public function getMeteoEtCoordonnees(string $ville): array
    {
        return [
            'meteo' => $this->getMeteo($ville),
            'coords' => $this->geocoder($ville),
        ];
    }
}