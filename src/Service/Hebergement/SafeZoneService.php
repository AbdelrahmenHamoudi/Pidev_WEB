<?php

namespace App\Service\Hebergement;

use App\Entity\Hebergement;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SafeZoneService
{
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        string $geoapifyKey
    ) {
        $this->apiKey = $geoapifyKey;
    }

    /**
     * Gets safety data for an accommodation, including coordinates, points of interest, and a safety score.
     * Uses caching to minimize API calls.
     */
    public function getSafetyData(Hebergement $hebergement): array
    {
        return $this->getSafetyDataByLocation($hebergement->getTitre(), (string) $hebergement->getId());
    }

    /**
     * Gets safety data by location name string.
     */
    public function getSafetyDataByLocation(string $location, ?string $identifier = null): array
    {
        $cleanLocation = trim($location);
        $cacheKey = 'safe_zone_loc_' . md5($cleanLocation . ($identifier ?? ''));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($cleanLocation) {
            $item->expiresAfter(86400); // 24h

            $coords = $this->geocode($cleanLocation);
            if (!$coords) {
                return $this->getEmptyResponse('Localisation introuvable : ' . $cleanLocation);
            }

            $lat = $coords['lat'];
            $lon = $coords['lon'];

            $police = $this->fetchPlaces($lat, $lon, 'service.police', 5000);
            $hospitals = $this->fetchPlaces($lat, $lon, 'healthcare.hospital', 5000);
            $activity = $this->fetchPlaces($lat, $lon, 'catering.restaurant,catering.cafe,leisure.park', 2000);

            $score = $this->calculateScore($police, $hospitals, $activity);

            return [
                'success' => true,
                'location' => $cleanLocation,
                'coords' => ['lat' => $lat, 'lon' => $lon],
                'score' => $score['total'],
                'status' => $score['status'],
                'color' => $score['color'],
                'details' => [
                    'police' => $police,
                    'hospitals' => $hospitals,
                    'activity' => $activity
                ]
            ];
        });
    }

    /**
     * Geocodes a search string using Geoapify API.
     */
    public function geocode(string $query): ?array
    {
        $cleanQuery = trim($query);
        if (empty($cleanQuery)) return null;

        $coords = $this->executeGeocode($cleanQuery);

        // Fallback: If no results and query contains " à " or " à", try to geocode the part after it
        if (!$coords && (stripos($cleanQuery, ' à ') !== false || stripos($cleanQuery, ' à') !== false)) {
            $parts = preg_split('/\sà\s/i', $cleanQuery);
            if (count($parts) > 1) {
                $locationQuery = trim(end($parts));
                $coords = $this->executeGeocode($locationQuery);
            }
        }

        return $coords;
    }

    /**
     * Internal helper to call the Geoapify API.
     */
    private function executeGeocode(string $text): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.geoapify.com/v1/geocode/search', [
                'query' => [
                    'text' => $text,
                    'apiKey' => $this->apiKey,
                    'filter' => 'countrycode:tn',
                    'bias' => 'countrycode:tn',
                    'lang' => 'fr',
                    'limit' => 1
                ]
            ]);

            $data = $response->toArray();
            if (!empty($data['features'])) {
                $feature = $data['features'][0];
                $coords = $feature['geometry']['coordinates'];
                $props = $feature['properties'];
                
                return [
                    'lon' => $coords[0], 
                    'lat' => $coords[1],
                    'city' => $props['city'] ?? $props['town'] ?? $props['village'] ?? $props['county'] ?? null
                ];
            }
        } catch (\Exception $e) {
            // Log error
        }
        return null;
    }

    /**
     * Fetches places from Geoapify Places API.
     */
    public function fetchPlaces(float $lat, float $lon, string $categories, int $radius): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.geoapify.com/v2/places', [
                'query' => [
                    'categories' => $categories,
                    'filter' => "circle:$lon,$lat,$radius",
                    'limit' => 10,
                    'apiKey' => $this->apiKey
                ]
            ]);

            $data = $response->toArray();
            $places = [];
            foreach ($data['features'] ?? [] as $feature) {
                $props = $feature['properties'];
                $places[] = [
                    'name' => $props['name'] ?? 'Inconnu',
                    'address' => $props['address_line2'] ?? '',
                    'lat' => $feature['geometry']['coordinates'][1],
                    'lon' => $feature['geometry']['coordinates'][0],
                    'distance' => $props['distance'] ?? 0
                ];
            }
            return $places;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Calculates a safety score (0-100) based on nearby services.
     */
    private function calculateScore(array $police, array $hospitals, array $activity): array
    {
        $policeScore = min(count($police) * 15, 40); // Max 40 pts
        $hospitalScore = min(count($hospitals) * 10, 30); // Max 30 pts
        $activityScore = min(count($activity) * 5, 30); // Max 30 pts

        $total = $policeScore + $hospitalScore + $activityScore;

        if ($total >= 75) {
            $status = 'Safe';
            $color = '#10b981'; // Green
        } elseif ($total >= 40) {
            $status = 'Moyen';
            $color = '#f59e0b'; // Amber
        } else {
            $status = 'Risqué';
            $color = '#ef4444'; // Red
        }

        return ['total' => $total, 'status' => $status, 'color' => $color];
    }

    private function getEmptyResponse(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'score' => 0,
            'status' => 'Inconnu',
            'color' => '#6b7280',
            'coords' => null,
            'details' => ['police' => [], 'hospitals' => [], 'activity' => []]
        ];
    }
}
