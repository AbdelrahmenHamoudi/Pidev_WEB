<?php

namespace App\Service\Hebergement;

use App\Entity\Hebergement;
use App\Entity\Reservation;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DynamicPricingService
{
    private string $openWeatherApiKey;
    private string $aviationstackApiKey;
    private string $configPath;
    private array $internalCache = [];

    public function __construct(
        private HttpClientInterface $httpClient,
        private TravelIntelligenceService $intelligenceService,
        string $projectDir
    ) {
        $this->openWeatherApiKey = $_ENV['OPENWEATHER_API_KEY'] ?? '';
        $this->aviationstackApiKey = $_ENV['AVIATIONSTACK_API_KEY'] ?? '';
        $this->configPath = $projectDir . '/public/uploads/pricing_config.json';
    }

    /**
     * Calculates a demand score (0-100) and recommended price adjustment.
     */
    public function getDynamicPricing(Hebergement $hebergement): array
    {
        $title = strtolower($hebergement->getTitre());
        $cityName = 'Tunis'; // Default

        $knownCities = ['tunis', 'sousse', 'djerba', 'hammamet', 'tabarka', 'tozeur', 'monastir', 'bizerte', 'ariana'];
        foreach ($knownCities as $city) {
            if (str_contains($title, $city)) {
                $cityName = ucfirst($city);
                break;
            }
        }

        $flightScore = $this->getFlightDemandScore($cityName);
        $weatherScore = $this->getWeatherDemandScore($cityName);
        $occupancyScore = $this->getOccupancyDemandScore($hebergement);
        $eventsScore = $this->getEventsDemandScore();

        $totalScore = (
            ($flightScore * 0.25) + 
            ($weatherScore * 0.25) + 
            ($occupancyScore * 0.40) + 
            ($eventsScore * 0.10)
        );

        $totalScore = (int)round($totalScore);

        $priceData = $this->calculateAdjustment($totalScore, (float)$hebergement->getPrixParNuit());
        
        return array_merge($priceData, [
            'score' => $totalScore,
            'isEnabled' => $this->isEnabled($hebergement->getId_hebergement()),
            'factors' => [
                'flights' => $flightScore,
                'weather' => $weatherScore,
                'occupancy' => $occupancyScore,
                'events' => $eventsScore
            ]
        ]);
    }

    /**
     * Centralized logic to calculate adjustment based on score.
     */
    public function calculateAdjustment(int $score, float $basePrice): array
    {
        $adjustment = 0;
        $reason = "";
        $status = "STABLE";

        if ($score <= 30) {
            $adjustment = -0.20;
            $reason = "Basse demande. Réduction recommandée pour stimuler les réservations.";
            $status = "DOWN";
        } elseif ($score <= 60) {
            $adjustment = 0;
            $reason = "Demande modérée. Prix standard maintenu.";
            $status = "STABLE";
        } elseif ($score <= 80) {
            $adjustment = 0.15;
            $reason = "Forte demande détectée. Hausse stratégique de +15%.";
            $status = "UP";
        } else {
            $adjustment = 0.30;
            $reason = "Pic de demande exceptionnel ! Hausse maximale de +30%.";
            $status = "UP_MAX";
        }

        return [
            'basePrice' => $basePrice,
            'recommendedPrice' => (int)round($basePrice * (1 + $adjustment)),
            'adjustment_pct' => $adjustment * 100,
            'explanation' => $reason,
            'status' => $status
        ];
    }

    /**
     * Aggregates data for the global pricing dashboard.
     */
    public function getGlobalMarketDashboard(array $allHebergements): array
    {
        $cities = ['Tunis', 'Djerba', 'Hammamet', 'Sousse'];
        $stats = [];

        foreach ($cities as $city) {
            $stats[$city] = [
                'flights' => $this->getFlightDemandScore($city),
                'weather' => $this->getWeatherDemandScore($city),
                'occupancy' => $this->getCityAverageOccupancy($city, $allHebergements),
                'events' => $this->getEventsDemandScore()
            ];
        }

        return $stats;
    }

    // --- State Management ---

    public function isEnabled(int $id): bool
    {
        $config = $this->loadConfig();
        return (bool)($config['accommodations'][$id]['enabled'] ?? false);
    }

    public function toggleEnabled(int $id): bool
    {
        $config = $this->loadConfig();
        $currentState = (bool)($config['accommodations'][$id]['enabled'] ?? false);
        $newState = !$currentState;
        
        $config['accommodations'][$id]['enabled'] = $newState;
        $this->saveConfig($config);
        
        return $newState;
    }

    private function loadConfig(): array
    {
        if (!file_exists($this->configPath)) {
            return ['accommodations' => [], 'global_settings' => ['auto_apply' => false]];
        }
        return json_decode(file_get_contents($this->configPath), true) ?: ['accommodations' => [], 'global_settings' => ['auto_apply' => false]];
    }

    private function saveConfig(array $config): void
    {
        file_put_contents($this->configPath, json_encode($config, JSON_PRETTY_PRINT));
    }

    // --- Helper Logic ---

    private function getCityAverageOccupancy(string $city, array $all): int
    {
        $count = 0;
        $totalScore = 0;
        foreach ($all as $h) {
            if (str_contains(strtolower($h->getTitre()), strtolower($city))) {
                $totalScore += $this->getOccupancyDemandScore($h);
                $count++;
            }
        }
        return $count > 0 ? (int)($totalScore / $count) : 40;
    }

    private function getFlightDemandScore(string $city): int
    {
        $cacheKey = 'flights_' . $city;
        if (isset($this->internalCache[$cacheKey])) {
            return $this->internalCache[$cacheKey];
        }

        if (!$this->aviationstackApiKey || $this->aviationstackApiKey === 'votre_cle_aviationstack_ici') {
            return 40;
        }

        $airportIata = $this->getNearestAirport($city);
        
        try {
            $response = $this->httpClient->request('GET', 'http://api.aviationstack.com/v1/flights', [
                'timeout' => 2,
                'query' => [
                    'access_key' => $this->aviationstackApiKey,
                    'arr_iata' => $airportIata,
                    'flight_status' => 'scheduled'
                ]
            ]);
            
            $data = $response->toArray();
            $count = count($data['data'] ?? []);

            if ($count > 30) $score = 90;
            elseif ($count > 15) $score = 70;
            elseif ($count > 5) $score = 50;
            else $score = 30;

            $this->internalCache[$cacheKey] = $score;
            return $score;
        } catch (\Exception $e) {
            return 40;
        }
    }

    private function getWeatherDemandScore(string $city): int
    {
        $cacheKey = 'weather_' . $city;
        if (isset($this->internalCache[$cacheKey])) {
            return $this->internalCache[$cacheKey];
        }

        if (!$this->openWeatherApiKey) return 40;

        try {
            $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                'timeout' => 2,
                'query' => [
                    'q' => $city . ',TN',
                    'appid' => $this->openWeatherApiKey,
                    'units' => 'metric'
                ]
            ]);
            $data = $response->toArray();
            $temp = $data['main']['temp'] ?? 20;
            $condition = $data['weather'][0]['main'] ?? 'Clear';

            $score = 50;
            if ($condition === 'Clear' || $condition === 'Clouds') $score += 20;
            if ($temp > 25 && $temp < 32) $score += 20;
            if ($condition === 'Rain' || $condition === 'Thunderstorm') $score -= 30;

            $score = max(0, min(100, $score));
            $this->internalCache[$cacheKey] = $score;
            return $score;
        } catch (\Exception $e) {
            return 40;
        }
    }

    private function getOccupancyDemandScore(Hebergement $hebergement): int
    {
        $now = new \DateTime();
        $endPeriod = (clone $now)->modify('+30 days');
        $occupiedDays = 0;

        foreach ($hebergement->getReservations() as $res) {
            if ($res->getStatutR() === 'CONFIRMEE') {
                $start = $res->getDateDebutR();
                $end = $res->getDateFinR();

                if ($start < $endPeriod && $end > $now) {
                    $actualStart = max($start, $now);
                    $actualEnd = min($end, $endPeriod);
                    $diff = $actualStart->diff($actualEnd);
                    $occupiedDays += $diff->days;
                }
            }
        }

        $rate = ($occupiedDays / 30) * 100;

        if ($rate > 80) return 100;
        if ($rate > 60) return 85;
        if ($rate > 40) return 60;
        if ($rate > 15) return 30;
        return 10;
    }

    private function getEventsDemandScore(): int
    {
        $now = new \DateTime();
        $peaks = [
            '12-31' => 100, '01-01' => 100, '03-20' => 80, 
            '07-25' => 80, '08-13' => 90,
        ];

        for ($i = 0; $i < 7; $i++) {
            $checkDate = (clone $now)->modify("+{$i} days")->format('m-d');
            if (isset($peaks[$checkDate])) return $peaks[$checkDate];
        }

        $month = (int)$now->format('m');
        if ($month >= 6 && $month <= 8) return 80;

        return 40;
    }

    private function getNearestAirport(string $city): string
    {
        $map = [
            'Tunis' => 'TUN', 'Sousse' => 'MIR', 'Monastir' => 'MIR',
            'Hammamet' => 'NBE', 'Djerba' => 'DJE', 'Tozeur' => 'TOE',
            'Tabarka' => 'TBJ', 'Bizerte' => 'TUN',
        ];

        return $map[$city] ?? 'TUN';
    }
}
