<?php

namespace App\Service\Hebergement;

use App\Entity\Hebergement;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GroqService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const DEFAULT_MODEL = 'llama-3.1-8b-instant';

    public function __construct(
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $params
    ) {}

    /**
     * Enhanced Market Analysis incorporating geocoding accuracy and internal database benchmarks.
     */
    public function getMarketAnalysis(
        Hebergement $hebergement, 
        float $occupancyRate,
        ?string $geocodedCity = null,
        float $systemAverage = 0
    ): array {
        $apiKey = $this->params->get('GROQ_API_KEY');
        
        if (!$apiKey || $apiKey === 'your_groq_api_key_here') {
            return [
                'error' => 'API Key not configured',
                'recommendation' => 'Veuillez configurer votre clé API Groq dans le fichier .env'
            ];
        }

        $titre = $hebergement->getTitre();
        $type = $hebergement->getTypeHebergement();
        $prix = (float) $hebergement->getPrixParNuit();
        $city = $geocodedCity ?: 'Tunisie';
        $currentMonth = (new \DateTime())->format('F');

        $prompt = <<<EOT
Tu es un expert en analyse du marché immobilier et hôtelier en Tunisie (Booking, Airbnb, Hotels.com).
Ta mission est de fournir une analyse de marché ULTRA-PRÉCISE pour cet hébergement.

DONNÉES D'ANALYSE :
- Localisation confirmée (Geocoding) : "$city"
- Titre original : "$titre"
- Type d'hébergement : "$type"
- Mois actuel (Saisonalité) : $currentMonth
- Prix actuel de l'hôte : $prix DT
- Taux d'occupation actuel de l'hôte : $occupancyRate%
- Moyenne interne du site pour cette ville : {$systemAverage} DT (Utilise ceci comme point d'ancrage)

INSTRUCTIONS :
1. Analyse le marché à $city pour le mois de $currentMonth.
2. Fournis des données moyennes RÉALISTES fondées sur les standards tunisiens actuels.
3. Compare le prix de l'hôte ($prix DT) avec le prix moyen du marché à $city.
4. Identifie si l'hôte est sur-positionné ou sous-positionné.
5. Suggère 3 actions concrètes (Ajustement prix, équipements, marketing) pour maximiser le revenu.

ATTENTION : Sois extrêmement réaliste sur les prix "Marché". Un hôtel 4* à Hammamet en été n'a pas le même prix qu'un studio à Tunis en hiver.

Réponds UNIQUEMENT au format JSON :
{
    "ville": "$city",
    "market_price": 0,
    "market_occupancy": 0,
    "market_revenue": 0,
    "price_diff_percent": 0,
    "occupancy_diff_percent": 0,
    "comparison_price_status": "compétitif / élevé / bas",
    "comparison_occupancy_status": "bon / moyen / faible",
    "recommendations": ["rec1", "rec2", "rec3"]
}
EOT;

        try {
            $response = $this->httpClient->request('POST', self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::DEFAULT_MODEL,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.5,
                    'response_format' => ['type' => 'json_object']
                ]
            ]);

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'];
            
            if (preg_match('/\{.*\}/s', $content, $matches)) {
                $content = $matches[0];
            }
            
            $decoded = json_decode($content, true);
            return $decoded ?: ['error' => 'Invalid AI response format', 'debug' => $content];

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (method_exists($e, 'getResponse')) {
                $errorMessage .= ' | Details: ' . $e->getResponse()->getContent(false);
            }
            return [
                'error' => 'AI Service Error: ' . $errorMessage,
                'recommendation' => 'Vérifiez votre clé API dans .env ou votre quota sur Groq Cloud.'
            ];
        }
    }


    public function getTravelSimulation(array $context): array
    {
        $apiKey = $this->params->get('GROQ_API_KEY');
        
        if (!$apiKey || $apiKey === 'your_groq_api_key_here') {
            return ['error' => 'API Key configuration missing'];
        }

        $prompt = <<<EOT
Tu es un moteur d’intelligence artificielle spécialisé dans la simulation de voyage intelligent (Digital Twin Travel) pour une plateforme de réservation d’hébergements.
Ta mission est de générer une analyse réaliste, structurée et exploitable permettant à un utilisateur de visualiser l’expérience réelle d’un séjour avant réservation.

---

📍 DONNÉES D’ENTRÉE (ENVIRONNEMENT) :
- Localisation : {$context['location']}
- Latitude : {$context['lat']}
- Longitude : {$context['lon']}
- Température : {$context['temperature']} °C
- Conditions météorologiques : {$context['weather']}
- Vitesse du vent : {$context['wind']} km/h
- Heure locale : {$context['time']}
- Date : {$context['date']}
- Type de zone : {$context['zone_type']}
- Densité des points d’intérêt : {$context['places_count']}

---

👤 PROFIL UTILISATEUR :
- Type de voyageur : {$context['user_profile']}
- Budget : {$context['budget']}
- Sensibilité au bruit : {$context['noise_preference']}
- Tolérance à la foule : {$context['crowd_preference']}

---

🧠 CONSIGNES D’ANALYSE :
1. Décrire de manière réaliste l’ambiance globale du lieu.
2. Analyser les conditions météorologiques et leur impact sur l’expérience utilisateur.
3. Estimer le niveau d’affluence (faible / modéré / élevé) avec justification logique.
4. Évaluer le niveau sonore (faible / modéré / élevé) selon la zone, l’heure et l’affluence.
5. Identifier la meilleure plage horaire pour visiter le lieu.
6. Fournir une projection des conditions à court terme (24h) et moyen terme (3 jours).
7. Adapter la recommandation selon le profil utilisateur.
8. Calculer un score global d’expérience utilisateur (/10). Sois CRITIQUE et PRÉCIS.

---

📌 FORMAT DE RÉPONSE OBLIGATOIRE (JSON) :
Réponds uniquement en JSON. Chaque valeur doit être une string, sauf le score qui est un number.
{
    "localisation": "...",
    "ambiance": "...",
    "meteo": "...",
    "temperature": "...",
    "vent": "...",
    "affluence": "...",
    "bruit": "...",
    "visite": "...",
    "previsions": "...",
    "analyse": "...",
    "score": 0,
    "recommandation": "..."
}
EOT;

        try {
            $response = $this->httpClient->request('POST', self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::DEFAULT_MODEL,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7
                ]
            ]);

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'];
            
            // Extract the first JSON object found in the response to ignore conversational filler
            if (preg_match('/\{.*\}/s', $content, $matches)) {
                $content = $matches[0];
            }
            
            $decoded = json_decode($content, true);
            if (!$decoded) {
                return ['error' => 'Invalid AI response format', 'debug' => $content];
            }
            
            return $decoded;

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (method_exists($e, 'getResponse')) {
                $errorMessage .= ' | Details: ' . $e->getResponse()->getContent(false);
            }
            return ['error' => 'AI Simulation Error: ' . $errorMessage];
        }
    }

    public function getChatResponse(array $messages, string $context = ''): array
    {
        $apiKey = $this->params->get('GROQ_API_KEY');
        
        if (!$apiKey || $apiKey === 'your_groq_api_key_here') {
            return ['error' => 'API Key configuration missing'];
        }

        // Add a system prompt for RE7LA.ai personality with site context
        $systemPromptContent = "Tu es RE7LA.ai (le 'jumeau intelligent' de KAYAK.ai), l'assistant de voyage intelligent du site RE7LA. Ton but est d'aider les utilisateurs à planifier leurs voyages, trouver des destinations, des vols, des hébergements et des conseils pratiques. Sois amical, professionnel et expert du voyage (particulièrement en Tunisie et à l'international). Réponds en français.\nIMPORTANT : Tu es strictement limité aux sujets de voyage. Si l'utilisateur pose une question en dehors du voyage (ex: code, cuisine, mathématiques, etc.), refuse poliment en disant que tu ne réponds qu'aux questions de voyage.";
        
        if (!empty($context)) {
            $systemPromptContent .= "\n\nVoici les hébergements ACTUELLEMENT disponibles sur notre plateforme RE7LA que tu peux recommander :\n" . $context;
            $systemPromptContent .= "\nSi l'utilisateur demande un hébergement, propose-lui en priorité ceux de cette liste en expliquant pourquoi ils correspondent à ses critères.";
        }

        $systemPrompt = [
            'role' => 'system',
            'content' => $systemPromptContent
        ];

        $fullMessages = array_merge([$systemPrompt], $messages);

        try {
            $response = $this->httpClient->request('POST', self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::DEFAULT_MODEL,
                    'messages' => $fullMessages,
                    'temperature' => 0.7,
                    'max_tokens' => 1024
                ]
            ]);

            $data = $response->toArray();
            return [
                'content' => $data['choices'][0]['message']['content'] ?? 'Désolé, je ne peux pas répondre pour le moment.',
                'role' => 'assistant'
            ];

        } catch (\Exception $e) {
            return ['error' => 'AI Chat Error: ' . $e->getMessage()];
        }
    }
}
