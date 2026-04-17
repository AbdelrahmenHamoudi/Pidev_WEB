<?php

namespace App\Service\Hebergement;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TravelIntelligenceService
{
    private const CACHE_TTL = 86400; // 24 hours

    private string $openWeatherApiKey = '';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private CurrencyService $currencyService,
        private string $rapidApiKey = ''
    ) {
        $this->rapidApiKey = $_ENV['RAPIDAPI_KEY'] ?? '';
        $this->openWeatherApiKey = $_ENV['OPENWEATHER_API_KEY'] ?? '';
    }

    /**
     * Get complete travel analysis for a Tunisian city.
     */
    public function getCityAnalysis(string $city): array
    {
        $city = ucfirst(strtolower($city));
        
        return $this->cache->get('travel_analysis_' . $city, function (ItemInterface $item) use ($city) {
            $item->expiresAfter(self::CACHE_TTL);
            
            $coords = $this->getCoords($city);
            // 1. Get Market Heat Indicator (OpenWeather)
            $marketHeat = $this->getMarketHeatIndicator($city);
            
            // 2. Get Weather History (Open-Meteo)
            $weatherData = $this->getMonthlyWeather($coords['lat'], $coords['lng']);
            
            // 3. Get Price Trends (Climate-Calibrated)
            $priceTrends = $this->getPriceTrends($city, $marketHeat);
            
            $monthlyData = [];
            $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

            $minPriceMonth = 0;
            $maxPriceMonth = 0;
            $lowPrice = 999999;
            $highPrice = 0;

            for ($i = 0; $i < 12; $i++) {
                $price = $priceTrends[$i];
                if ($price < $lowPrice) { $lowPrice = $price; $minPriceMonth = $i; }
                if ($price > $highPrice) { $highPrice = $price; $maxPriceMonth = $i; }

                $monthlyData[] = [
                    'month' => $months[$i],
                    'temp' => $weatherData[$i]['temp'],
                    'rain' => $weatherData[$i]['rain'],
                    'price' => $price,
                    'status' => $this->getPriceStatus($price, $priceTrends)
                ];
            }

            $recommendation = $this->generateRecommendation($monthlyData, $minPriceMonth);

            return [
                'city' => $city,
                'coords' => $coords,
                'monthlyData' => $monthlyData,
                'cheapestMonth' => $months[$minPriceMonth],
                'expensiveMonth' => $months[$maxPriceMonth],
                'recommendation' => $recommendation,
                'updatedAt' => new \DateTime()
            ];
        });
    }

    private function getCoords(string $city): array
    {
        $coords = [
            'Tunis' => ['lat' => 36.8065, 'lng' => 10.1815],
            'Sousse' => ['lat' => 35.8256, 'lng' => 10.6369],
            'Djerba' => ['lat' => 33.8075, 'lng' => 10.8451],
            'Hammamet' => ['lat' => 36.4, 'lng' => 10.6167],
            'Tabarka' => ['lat' => 36.95, 'lng' => 8.75],
        ];

        return $coords[$city] ?? $coords['Tunis'];
    }

    private function getMonthlyWeather(float $lat, float $lng): array
    {
        return $this->cache->get("weather_stats_{$lat}_{$lng}", function (ItemInterface $item) use ($lat, $lng) {
            $item->expiresAfter(self::CACHE_TTL * 7); // Cache for 7 days
            
            try {
                // Fetch daily data for the year 2023 to compute monthly climatology
                $response = $this->httpClient->request('GET', 'https://archive-api.open-meteo.com/v1/archive', [
                    'query' => [
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'start_date' => '2023-01-01',
                        'end_date' => '2023-12-31',
                        'daily' => 'temperature_2m_mean,rain_sum',
                        'timezone' => 'auto'
                    ]
                ]);
                
                $data = $response->toArray();
                $daily = $data['daily'];
                
                $monthlyTemp = array_fill(0, 12, []);
                $monthlyRain = array_fill(0, 12, []);
                
                foreach ($daily['time'] as $index => $date) {
                    $month = (int)date('n', strtotime($date)) - 1;
                    $monthlyTemp[$month][] = $daily['temperature_2m_mean'][$index];
                    $monthlyRain[$month][] = $daily['rain_sum'][$index];
                }
                
                $result = [];
                for ($i = 0; $i < 12; $i++) {
                    $avgTemp = array_sum($monthlyTemp[$i]) / count($monthlyTemp[$i]);
                    $totalRain = array_sum($monthlyRain[$i]);
                    
                    $result[] = [
                        'temp' => round($avgTemp),
                        'rain' => round($totalRain)
                    ];
                }
                
                return $result;
            } catch (\Exception $e) {
                // Fallback to defaults if API fails
                return [
                    ['temp' => 12, 'rain' => 60], ['temp' => 13, 'rain' => 50], ['temp' => 15, 'rain' => 45],
                    ['temp' => 18, 'rain' => 35], ['temp' => 22, 'rain' => 20], ['temp' => 26, 'rain' => 10],
                    ['temp' => 29, 'rain' => 2],  ['temp' => 30, 'rain' => 5],  ['temp' => 27, 'rain' => 30],
                    ['temp' => 22, 'rain' => 50], ['temp' => 17, 'rain' => 55], ['temp' => 13, 'rain' => 65]
                ];
            }
        });
    }

    private function getMarketHeatIndicator(string $city): float
    {
        if (!$this->openWeatherApiKey || $this->openWeatherApiKey === 'votre_cle_ici') {
            return 1.0; // Default factor
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'q' => $city . ',TN',
                    'appid' => $this->openWeatherApiKey,
                    'units' => 'metric'
                ]
            ]);
            $data = $response->toArray();
            $currentTemp = $data['main']['temp'] ?? 20;
            
            // If current temp is > 28°C, it's peak season demand (+15%)
            if ($currentTemp > 28) return 1.15;
            // If it's nice (22-28°C), it's high demand (+5%)
            if ($currentTemp > 22) return 1.05;
            
            return 1.0;
        } catch (\Exception $e) {
            return 1.0;
        }
    }

    private function getPriceTrends(string $city, float $weatherFactor = 1.0): array
    {
        // 1. Definition of the historical premium seasonality curve
        $curves = [
            'Tunis'  => [0.95, 1.0, 1.05, 1.15, 1.25, 1.4, 1.55, 1.6, 1.4, 1.25, 1.1, 0.95],
            'Djerba' => [0.8, 0.85, 1.0, 1.3, 1.6, 2.3, 2.7, 2.8, 1.9, 1.3, 1.0, 0.85],
            'Sousse' => [0.8, 0.85, 1.0, 1.3, 1.6, 2.2, 2.6, 2.7, 1.8, 1.3, 0.95, 0.8],
        ];

        $curve = $curves[$city] ?? $curves['Tunis'];
        
        // 2. Calibrated baselines for a Premium Experience (April-based)
        $basePrice = 280; // Tunis
        if ($city === 'Djerba' || $city === 'Sousse') $basePrice = 320;

        // Apply real market heat factor from OpenWeather
        $basePrice = $basePrice * $weatherFactor;

        // 3. Scale the curve
        $currentMonth = (int)date('n') - 1;
        $currentCurveVal = $curve[$currentMonth];
        
        $finalTrends = [];
        foreach ($curve as $val) {
            $finalTrends[] = round(($basePrice / $currentCurveVal) * $val);
        }

        return $finalTrends;
    }

    private function getPriceStatus(float $price, array $allPrices): string
    {
        $avg = array_sum($allPrices) / count($allPrices);
        if ($price < $avg * 0.9) return 'cheap';
        if ($price > $avg * 1.1) return 'expensive';
        return 'normal';
    }

    private function generateRecommendation(array $data, int $minMonthIdx): array
    {
        $currentMonthIdx = (int)date('n') - 1;
        $currentPrice = $data[$currentMonthIdx]['price'];
        
        // Find Max price for saving comparison
        $maxPrice = 0;
        foreach($data as $d) if($d['price'] > $maxPrice) $maxPrice = $d['price'];
        
        // Potential saving compared to highest month
        $savingMax = round((($maxPrice - $currentPrice) / $maxPrice) * 100);

        if ($data[$currentMonthIdx]['status'] === 'cheap') {
            return [
                'action' => 'Réserver maintenant',
                'color' => 'success',
                'message' => 'Les tarifs sont actuellement au plus bas pour cette destination.',
                'saving' => $savingMax
            ];
        }

        $minPrice = $data[$minMonthIdx]['price'];
        $potentialSaving = round((($currentPrice - $minPrice) / $currentPrice) * 100);

        if ($potentialSaving > 15) {
            return [
                'action' => 'Attendre pour payer moins',
                'color' => 'warning',
                'message' => 'Les prix devraient baisser de ' . $potentialSaving . '% dans les mois à venir.',
                'saving' => $potentialSaving
            ];
        }

        return [
            'action' => 'Surveiller les prix',
            'color' => 'info',
            'message' => 'Le meilleur mois pour économiser du budget est ' . $data[$minMonthIdx]['month'] . '.',
            'saving' => $savingMax
        ];
    }
    /**
     * Finds the best destination matching the user's criteria.
     */
    public function getMatchingDestinations(array $criteria): array
    {
        $destinations = $this->getDestinationDatabase();
        $scoredDestinations = [];

        foreach ($destinations as $dest) {
            $score = 0;

            // 1. Budget Score (40%)
            $budgetScore = $this->calculateBudgetScore($criteria['budget'], $dest['budget']);
            $score += $budgetScore * 40;

            // 2. Ambiance Score (40%)
            $ambianceScore = $criteria['ambiance'] === $dest['ambiance'] ? 1.0 : 0;
            $score += $ambianceScore * 40;

            // 3. Duration Score (20%)
            $durationScore = $this->calculateDurationScore($criteria['duration'], $dest['duration']);
            $score += $durationScore * 20;

            $dest['match_score'] = round($score / 10, 1);
            $scoredDestinations[] = $dest;
        }

        // Sort by score descending
        usort($scoredDestinations, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

        return [
            'top' => $scoredDestinations[0],
            'alternatives' => array_slice($scoredDestinations, 1, 2)
        ];
    }

    private function calculateBudgetScore(string $userBudget, string $destBudget): float
    {
        if ($userBudget === $destBudget) return 1.0;
        
        $order = ['low', 'medium', 'high'];
        $userIdx = array_search($userBudget, $order);
        $destIdx = array_search($destBudget, $order);
        
        $diff = abs($userIdx - $destIdx);
        if ($diff === 1) return 0.5;
        return 0;
    }

    private function calculateDurationScore(string $userDuration, string $destDuration): float
    {
        if ($userDuration === $destDuration) return 1.0;
        
        $order = ['short', 'mid', 'long'];
        $userIdx = array_search($userDuration, $order);
        $destIdx = array_search($destDuration, $order);
        
        $diff = abs($userIdx - $destIdx);
        if ($diff === 1) return 0.5;
        return 0;
    }

    private function getDestinationDatabase(): array
    {
        return [
            [
                'name' => 'Djerba',
                'description' => "L'île aux sables d'or, mix parfait de détente et d'histoire.",
                'budget_avg' => '150-400 DT',
                'budget' => 'medium',
                'ambiance' => 'festif',
                'duration' => 'long',
                'best_period' => 'Avril à Octobre',
                'image' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800'
            ],
            [
                'name' => 'Tozeur',
                'description' => "La porte du désert, oasis luxuriante et architectures de briques uniques.",
                'budget_avg' => '120-350 DT',
                'budget' => 'medium',
                'ambiance' => 'aventure',
                'duration' => 'mid',
                'best_period' => 'Novembre à Mars',
                'image' => 'https://images.unsplash.com/photo-1590059530462-8e10dcf919e1?w=800'
            ],
            [
                'name' => 'Sidi Bou Said',
                'description' => "Le joyau bleu et blanc surplombant la Méditerranée, paradis des artistes.",
                'budget_avg' => '300-800 DT',
                'budget' => 'high',
                'ambiance' => 'calme',
                'duration' => 'short',
                'best_period' => 'Toute l\'année',
                'image' => 'https://images.unsplash.com/photo-1534484323631-64d9e03d3606?w=800'
            ],
            [
                'name' => 'Hammamet',
                'description' => "Le Saint-Tropez tunisien, entre mer turquoise et vie nocturne animée.",
                'budget_avg' => '200-600 DT',
                'budget' => 'medium',
                'ambiance' => 'festif',
                'duration' => 'mid',
                'best_period' => 'Mai à Septembre',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800'
            ],
            [
                'name' => 'Tabarka',
                'description' => "Entre mer et fôrets de chênes-lièges, paradis des plongeurs et randonneurs.",
                'budget_avg' => '80-250 DT',
                'budget' => 'low',
                'ambiance' => 'nature',
                'duration' => 'mid',
                'best_period' => 'Juin à Août',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=800'
            ],
            [
                'name' => 'Bizerte',
                'description' => "Ville de charme avec son vieux port et ses plages sauvages encore préservées.",
                'budget_avg' => '70-180 DT',
                'budget' => 'low',
                'ambiance' => 'calme',
                'duration' => 'short',
                'best_period' => 'Mars à Juin',
                'image' => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=800'
            ],
            [
                'name' => 'Ain Draham',
                'description' => "La Suisse tunisienne, idéale pour la randonnée et la nature brute.",
                'budget_avg' => '60-150 DT',
                'budget' => 'low',
                'ambiance' => 'nature',
                'duration' => 'short',
                'best_period' => 'Hiver pour la neige',
                'image' => 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=800'
            ]
        ];
    }
}

