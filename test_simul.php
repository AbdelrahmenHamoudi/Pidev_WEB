<?php
require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

(new Dotenv())->bootEnv(__DIR__ . '/.env');
$apiKey = $_ENV['GROQ_API_KEY'] ?? 'missing';

class TestGroqService {
    public function getTravelSimulation(array $context, string $apiKey): array
    {
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

        $client = HttpClient::create();
        try {
            $response = $client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.1-8b-instant',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7,
                    'response_format' => ['type' => 'json_object']
                ]
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (method_exists($e, 'getResponse')) {
                $errorMessage .= "\nDETAILS:\n" . $e->getResponse()->getContent(false);
            }
            return ['error' => $errorMessage];
        }
    }
}

$context = [
    'location' => 'Tunis',
    'lat' => 36.8,
    'lon' => 10.2,
    'temperature' => 25,
    'weather' => 'Ensoleillé',
    'wind' => 10,
    'time' => '14:00',
    'date' => '2023-10-10',
    'zone_type' => 'Urbain',
    'places_count' => 15,
    'user_profile' => 'Solo',
    'budget' => '100',
    'noise_preference' => 'Faible',
    'crowd_preference' => 'Faible'
];

$svc = new TestGroqService();
print_r($svc->getTravelSimulation($context, $apiKey));
