<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class VirtualTourService
{
    // Contexte culturel par lieu — même logique que JavaFX
    private array $contextes = [
        'sidi bou said' => "Village emblématique au sommet d'une falaise surplombant le golfe de Tunis. Maisons blanches et portes bleues cobalt, ruelles pavées, café des Délices, palais Baron d'Erlanger. A inspiré Paul Klee et Simone de Beauvoir.",
        'carthage'      => "Ancienne métropole phénicienne fondée en 814 av. J.-C. Site UNESCO : thermes d'Antonin, tophet, port circulaire militaire, amphithéâtre. Détruite par Rome en 146 av. J.-C.",
        'djerba'        => "Île du bonheur du sud tunisien. Synagogue de la Ghriba, village de Guellala, Houmt Souk, plages de Sidi Mahrez, communauté juive millénaire.",
        'sousse'        => "Perle du Sahel, troisième ville de Tunisie. Médina UNESCO, Ribat du IXe siècle, musée archéologique, Grande Mosquée, port antique.",
        'kairouan'      => "Quatrième ville sainte de l'Islam. Grande Mosquée (670 ap. J.-C.), Bassins des Aghlabides, mausolée de Sidi Sahbi, makhroudh aux dattes.",
    ];

    public function __construct(
        private HttpClientInterface $client,
        private string              $groqApiKey,
        private string              $projectDir,
    ) {}

    // ══════════════════════════════════════════════════
    //  POINT D'ENTRÉE PRINCIPAL
    // ══════════════════════════════════════════════════

    public function generer(string $lieu, string $langue = 'français'): array
    {
        $photos    = $this->fetchPhotosWikimedia($lieu);
        $narration = $this->genererNarration($lieu, $langue);
        $audioPath = $this->genererAudio($narration, $langue);

        return [
            'lieu'      => $lieu,
            'photos'    => $photos,
            'narration' => $narration,
            'audio'     => $audioPath,
            'langue'    => $langue,
        ];
    }

    // ══════════════════════════════════════════════════
    //  PHOTOS — Wikimedia Commons
    // ══════════════════════════════════════════════════

    private function fetchPhotosWikimedia(string $lieu, int $count = 6): array
    {
        try {
            $response = $this->client->request('GET',
                'https://commons.wikimedia.org/w/api.php', [
                    'query' => [
                        'action'       => 'query',
                        'generator'    => 'search',
                        'gsrnamespace' => 6,
                        'gsrsearch'    => $lieu . ' tunisia',
                        'gsrlimit'     => $count,
                        'prop'         => 'imageinfo',
                        'iiprop'       => 'url|size',
                        'iiurlwidth'   => 1200,
                        'format'       => 'json',
                    ],
                    'headers' => ['User-Agent' => 'RE7LA-App/1.0']
                ]
            );

            $data  = $response->toArray();
            $pages = $data['query']['pages'] ?? [];
            $urls  = [];

            foreach ($pages as $page) {
                $info = $page['imageinfo'][0] ?? null;
                if (!$info) continue;

                $url   = $info['thumburl'] ?? $info['url'] ?? '';
                $width = $info['thumbwidth'] ?? 0;

                if ($url && !str_ends_with($url, '.svg') && $width >= 400) {
                    $urls[] = $url;
                }
            }

            // Fallback Unsplash si pas assez de photos
            if (count($urls) < 3) {
                $urls = array_merge($urls, $this->fetchPhotosUnsplash($lieu, 6 - count($urls)));
            }

            return array_slice($urls, 0, 6);

        } catch (\Exception $e) {
            return $this->fetchPhotosUnsplash($lieu, 6);
        }
    }

    private function fetchPhotosUnsplash(string $lieu, int $count = 6): array
    {
        // Photos de secours depuis Unsplash (sans clé API)
        $query = urlencode($lieu . ' tunisia');
        return [
            "https://source.unsplash.com/1200x800/?{$query},1",
            "https://source.unsplash.com/1200x800/?{$query},2",
            "https://source.unsplash.com/1200x800/?tunisia,travel",
        ];
    }

    // ══════════════════════════════════════════════════
    //  NARRATION — Groq API (LLaMA 70B)
    // ══════════════════════════════════════════════════

    private function genererNarration(string $lieu, string $langue): string
    {
        $contexte = $this->getContexte($lieu);

        $systemPrompt = "Tu es un guide touristique expert et narrateur passionné. "
            . "Tu génères des narrations immersives, sensorielles et historiquement précises.";

        $userPrompt = "Génère une narration de visite virtuelle pour : {$lieu} (Tunisie)\n\n"
            . "CONTEXTE CULTUREL :\n{$contexte}\n\n"
            . "CONSIGNES :\n"
            . "- Langue : {$langue}\n"
            . "- 140-160 mots (60-70 secondes à l'oral)\n"
            . "- Structure : (1) Ouverture sensorielle (2) Histoire avec dates précises "
            . "(3) Ce que le visiteur voit (4) Invitation finale\n"
            . "- Texte continu, pas de titres ni tirets\n"
            . "- Conçu pour être lu à voix haute";

        try {
            $response = $this->client->request('POST',
                'https://api.groq.com/openai/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->groqApiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'       => 'llama-3.3-70b-versatile',
                        'messages'    => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user',   'content' => $userPrompt],
                        ],
                        'temperature' => 0.75,
                        'max_tokens'  => 400,
                    ]
                ]
            );

            $data = $response->toArray();
            return $data['choices'][0]['message']['content'] ?? 'Narration indisponible.';

        } catch (\Exception $e) {
            return "Bienvenue à {$lieu}, joyau de la Tunisie. "
                 . "Ce lieu fascinant vous invite à découvrir son histoire millénaire "
                 . "et ses trésors culturels uniques.";
        }
    }

    // ══════════════════════════════════════════════════
    //  AUDIO — gTTS via Process Symfony
    // ══════════════════════════════════════════════════

    private function genererAudio(string $texte, string $langue): ?string
    {
        $langCode  = $langue === 'français' ? 'fr' : 'ar';
        $audioDir  = $this->projectDir . '/public/audio/tours/';
        $audioFile = 'tour_' . time() . '.mp3';
        $audioPath = $audioDir . $audioFile;

        // Créer le dossier si nécessaire
        if (!is_dir($audioDir)) {
            mkdir($audioDir, 0777, true);
        }

        // Nettoyer le texte
        $texte = str_replace(["'", '"', "\n"], ["\\'", '', ' '], $texte);

        // Script Python inline
        $pythonScript = sprintf(
            "from gtts import gTTS; tts = gTTS(text='%s', lang='%s', slow=False); tts.save('%s'); print('OK')",
            $texte,
            $langCode,
            str_replace('\\', '/', $audioPath)
        );

        // Essayer plusieurs commandes Python
        foreach (['python', 'python3', 'py'] as $python) {
            try {
                $process = new Process([$python, '-c', $pythonScript]);
                $process->setTimeout(30);
                $process->run();

                if ($process->isSuccessful() && file_exists($audioPath) && filesize($audioPath) > 1000) {
                    return '/audio/tours/' . $audioFile; // chemin public
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    // ══════════════════════════════════════════════════
    //  HELPER — Contexte culturel
    // ══════════════════════════════════════════════════

    private function getContexte(string $lieu): string
    {
        $lieuLower = strtolower(trim($lieu));
        foreach ($this->contextes as $key => $contexte) {
            if (str_contains($lieuLower, $key)) {
                return $contexte;
            }
        }
        return "Site touristique tunisien au carrefour des civilisations berbère, "
             . "phénicienne, romaine, arabo-islamique et ottomane.";
    }
}