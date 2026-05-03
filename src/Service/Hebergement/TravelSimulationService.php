<?php

namespace App\Service\Hebergement;

use App\Entity\Hebergement;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TravelSimulationService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private SafeZoneService $safeZoneService,
        private GroqService $groqService
    ) {}

    public function generateSimulation(Hebergement $hebergement, array $userPreferences): array
    {
        // 1. Get Coordinates
        $coords = $this->safeZoneService->geocode($hebergement->getTitre());
        if (!$coords) {
            return ['error' => 'Unable to geocode accommodation location.'];
        }

        $lat = $coords['lat'];
        $lon = $coords['lon'];

        // 2. Fetch Weather Data (Open-Meteo)
        $weatherData = $this->fetchWeather($lat, $lon);

        // 3. Fetch Nearby Places Count
        $nearbyPlaces = $this->safeZoneService->fetchPlaces($lat, $lon, 'catering.restaurant,catering.cafe,leisure.park,tourism.attraction', 2000);
        $placesCount = count($nearbyPlaces);

        // 4. Determine Zone Type
        $zoneType = $this->determineZoneType($hebergement, $placesCount);

        // 5. Prepare Context
        // ✅ CORRECTION 3 : suppression de la clé 'location' dupliquée (était présente 2 fois)
        $context = [
            'location'         => $hebergement->getTitre(),
            'lat'              => $lat,
            'lon'              => $lon,
            'temperature'      => $weatherData['temp'] ?? '22',
            'weather'          => $weatherData['condition'] ?? 'Ensoleillé',
            'wind'             => $weatherData['wind'] ?? '10',
            'places_count'     => $placesCount,
            'zone_type'        => $zoneType,
            'time'             => (new \DateTime())->format('H:i'),
            'date'             => (new \DateTime())->format('d/m/Y'),
            'user_profile'     => $userPreferences['profile'] ?? 'Aventure',
            'budget'           => $userPreferences['budget'] ?? 'Moyen',
            'noise_preference' => $userPreferences['noise'] ?? 'faible',
            'crowd_preference' => $userPreferences['crowd'] ?? 'moyen',
        ];

        // 6. Call Groq for Simulation
        return $this->groqService->getTravelSimulation($context);
    }

    private function fetchWeather(float $lat, float $lon): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
                'query' => [
                    'latitude'  => $lat,
                    'longitude' => $lon,
                    'current'   => 'temperature_2m,wind_speed_10m,weather_code',
                    'timezone'  => 'auto'
                ]
            ]);

            $data    = $response->toArray();
            $current = $data['current'] ?? [];

            return [
                'temp'      => $current['temperature_2m'] ?? '20',
                'wind'      => $current['wind_speed_10m'] ?? '5',
                'condition' => $this->mapWeatherCode($current['weather_code'] ?? 0)
            ];
        } catch (\Exception $e) {
            return [
                'temp'      => '22',
                'wind'      => '12',
                'condition' => 'Eclaircies'
            ];
        }
    }

    private function mapWeatherCode(int $code): string
    {
        $mapping = [
            0  => 'Ciel dégagé',
            1  => 'Principalement dégagé',
            2  => 'Partiellement nuageux',
            3  => 'Couvert',
            45 => 'Brouillard',
            48 => 'Brouillard givrant',
            51 => 'Bruine légère',
            53 => 'Bruine modérée',
            55 => 'Bruine dense',
            61 => 'Pluie légère',
            63 => 'Pluie modérée',
            65 => 'Pluie forte',
            71 => 'Neige légère',
            80 => 'Averses de pluie légères',
            95 => 'Orage'
        ];

        return $mapping[$code] ?? 'Variable';
    }

    private function determineZoneType(Hebergement $hebergement, int $placesCount): string
    {
        $type = strtolower($hebergement->getTypeHebergement());

        if ($placesCount > 15) return 'Centre-ville / Zone très animée';
        if ($placesCount > 5)  return 'Zone touristique dynamique';
        if (str_contains($type, 'villa') || str_contains($type, 'maison')) return 'Quartier résidentiel calme';
        if (str_contains($type, 'hotel') || str_contains($type, 'resort')) return 'Complexe hôtelier';

        return 'Zone calme et authentique';
    }
}