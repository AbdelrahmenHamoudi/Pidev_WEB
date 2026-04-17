<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Process\Process;

class VirtualTourService
{
    private array $contextes = [
        'sidi bou said' => "Village emblématique au sommet d'une falaise surplombant le golfe de Tunis. Maisons blanches et portes bleues cobalt, ruelles pavées, café des Délices, palais Baron d'Erlanger. A inspiré Paul Klee et Simone de Beauvoir.",
        'carthage'      => "Ancienne métropole phénicienne fondée en 814 av. J.-C. Site UNESCO : thermes d'Antonin, tophet, port circulaire militaire, amphithéâtre. Détruite par Rome en 146 av. J.-C.",
        'djerba'        => "Île du bonheur du sud tunisien. Synagogue de la Ghriba, village de Guellala, Houmt Souk, plages de Sidi Mahrez, communauté juive millénaire.",
        'sousse'        => "Perle du Sahel, troisième ville de Tunisie. Médina UNESCO, Ribat du IXe siècle, musée archéologique, Grande Mosquée, port antique.",
        'kairouan'      => "Quatrième ville sainte de l'Islam. Grande Mosquée (670 ap. J.-C.), Bassins des Aghlabides, mausolée de Sidi Sahbi, makhroudh aux dattes.",
    ];

    // Lieux autorisés (whitelist sécurité)
    private array $lieuxAutorises = [
        'sidi bou said',
        'carthage',
        'djerba',
        'sousse',
        'kairouan',
    ];

    public function __construct(
        private HttpClientInterface $client,
        private CacheInterface      $cache,
        private string              $groqApiKey,
        private string              $projectDir,
    ) {}

    // ══════════════════════════════════════════════════
    //  VALIDATION
    // ══════════════════════════════════════════════════

    public function estLieuAutorise(string $lieu): bool
{
    if (empty($this->lieuxDynamiques)) {
        return strlen(trim($lieu)) >= 2;
    }

    $lieuLower = strtolower(trim($lieu));
    foreach ($this->lieuxDynamiques as $autorise) {
        if (str_contains($lieuLower, $autorise) || str_contains($autorise, $lieuLower)) {
            return true;
        }
    }
    return false;
}

    public function getLieuxAutorises(): array
    {
        return $this->lieuxAutorises;
    }

    // ══════════════════════════════════════════════════
    //  POINT D'ENTRÉE PRINCIPAL (avec cache 24h)
    // ══════════════════════════════════════════════════

    public function generer(string $lieu, string $langue = 'français'): array
{
    $cacheKey = 'tour_' . md5(strtolower($lieu) . $langue);

    return $this->cache->get($cacheKey, function (ItemInterface $item) use ($lieu, $langue) {
        $item->expiresAfter(86400);

        $photos    = $this->fetchPhotosWikimedia($lieu);
        $narration = $this->genererNarration($lieu, $langue);
        // ❌ retiré : $audioPath = $this->genererAudio(...)

        return [
            'lieu'      => $lieu,
            'photos'    => $photos,
            'narration' => $narration,
            'audio'     => null,   // sera chargé séparément
            'langue'    => $langue,
        ];
    });
}
public function getNarration(string $lieu, string $langue): string
{
    $data = $this->generer($lieu, $langue); // depuis le cache
    return $data['narration'];
}

public function genererAudioPublic(string $texte, string $langue): ?string
{
    return $this->genererAudio($texte, $langue); // rend la méthode private -> public
}

    // ══════════════════════════════════════════════════
    //  QUIZ CULTUREL (généré par IA)
    // ══════════════════════════════════════════════════

    public function genererQuiz(string $lieu, string $langue = 'français'): array
    {
        $cacheKey = 'quiz_' . md5(strtolower($lieu) . $langue);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($lieu, $langue) {
            $item->expiresAfter(86400);

            $isAr = $langue === 'arabe';

            $prompt = $isAr
                ? "أنشئ 3 أسئلة اختيار من متعدد ثقافية وتاريخية عن {$lieu} في تونس.\n"
                  . "أعِد JSON فقط، بدون أي نص إضافي، بهذا الشكل الدقيق:\n"
                  . '[{"question":"...","options":["أ...","ب...","ج...","د..."],"correct":0}]'
                : "Génère 3 QCM culturels et historiques sur {$lieu} en Tunisie.\n"
                  . "Retourne uniquement du JSON valide, sans texte supplémentaire, sous cette forme exacte:\n"
                  . '[{"question":"...","options":["A...","B...","C...","D..."],"correct":0}]';

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
                                ['role' => 'user', 'content' => $prompt],
                            ],
                            'temperature' => 0.4,
                            'max_tokens'  => 600,
                        ]
                    ]
                );

                $data = $response->toArray();
                $raw  = $data['choices'][0]['message']['content'] ?? '[]';

                // Nettoyer les backticks éventuels
                $raw = preg_replace('/```json|```/', '', $raw);
                $raw = trim($raw);

                $quiz = json_decode($raw, true);
                return is_array($quiz) ? $quiz : $this->quizFallback($lieu, $isAr);

            } catch (\Exception $e) {
                return $this->quizFallback($lieu, $isAr);
            }
        });
    }

    private function quizFallback(string $lieu, bool $isAr): array
    {
        if ($isAr) {
            return [[
                'question' => "ما هي الحضارة التي بنت {$lieu}؟",
                'options'  => ['الرومانية', 'الفينيقية', 'البيزنطية', 'العثمانية'],
                'correct'  => 1,
            ]];
        }
        return [[
            'question' => "Quelle civilisation a fondé {$lieu} ?",
            'options'  => ['Romaine', 'Phénicienne', 'Byzantine', 'Ottomane'],
            'correct'  => 1,
        ]];
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
        $query = urlencode($lieu . ' tunisia');
        return [
            "https://source.unsplash.com/1200x800/?{$query},architecture",
            "https://source.unsplash.com/1200x800/?{$query},culture",
            "https://source.unsplash.com/1200x800/?tunisia,travel",
        ];
    }

    // ══════════════════════════════════════════════════
    //  NARRATION — Groq API (LLaMA 70B) bilingue
    // ══════════════════════════════════════════════════

    private function genererNarration(string $lieu, string $langue): string
    {
        $contexte = $this->getContexte($lieu, $langue);
        $isAr     = $langue === 'arabe';

        $systemPrompt = $isAr
            ? "أنت مرشد سياحي خبير وراوٍ متحمس. تُنشئ تعليقاً صوتياً غامراً وحسياً ودقيقاً من الناحية التاريخية، مصمَّماً للقراءة بصوت عالٍ."
            : "Tu es un guide touristique expert et narrateur passionné. Tu génères des narrations immersives, sensorielles et historiquement précises.";

        $userPrompt = $isAr
            ? "أنشئ تعليقاً للجولة الافتراضية لـ: {$lieu} (تونس)\n\n"
              . "السياق الثقافي:\n{$contexte}\n\n"
              . "التعليمات:\n"
              . "- اللغة: العربية الفصحى الميسّرة\n"
              . "- 140 إلى 160 كلمة (60 إلى 70 ثانية شفهياً)\n"
              . "- البنية: (1) افتتاح حسّي (2) تاريخ بتواريخ دقيقة (3) ما يراه الزائر (4) دعوة ختامية\n"
              . "- نص متواصل بلا عناوين أو شرطات\n"
              . "- مُصمَّم للقراءة بصوت عالٍ"
            : "Génère une narration de visite virtuelle pour : {$lieu} (Tunisie)\n\n"
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
            return $data['choices'][0]['message']['content'] ?? $this->narrationFallback($lieu, $isAr);

        } catch (\Exception $e) {
            return $this->narrationFallback($lieu, $isAr);
        }
    }

    private function narrationFallback(string $lieu, bool $isAr): string
    {
        return $isAr
            ? "مرحباً بكم في {$lieu}، جوهرة تونس. يدعوكم هذا المكان الرائع لاكتشاف تاريخه العريق وكنوزه الثقافية الفريدة."
            : "Bienvenue à {$lieu}, joyau de la Tunisie. Ce lieu fascinant vous invite à découvrir son histoire millénaire et ses trésors culturels uniques.";
    }

    // ══════════════════════════════════════════════════
    //  AUDIO — gTTS via fichier temporaire (sécurisé)
    // ══════════════════════════════════════════════════

    private function genererAudio(string $texte, string $langue): ?string
    {
        $langCode = match ($langue) {
            'arabe'   => 'ar',
            'anglais' => 'en',
            default   => 'fr',
        };

        // Nom de fichier basé sur le hash — évite les doublons
        $audioDir  = $this->projectDir . '/public/audio/tours/';
        $audioFile = 'tour_' . md5($texte . $langue) . '.mp3';
        $audioPath = $audioDir . $audioFile;

        // Retourner directement si déjà généré
        if (file_exists($audioPath) && filesize($audioPath) > 1000) {
            return '/audio/tours/' . $audioFile;
        }

        if (!is_dir($audioDir)) {
            mkdir($audioDir, 0777, true);
        }

        // Écrire le texte dans un fichier temporaire (évite l'injection de commande)
        $tmpFile = tempnam(sys_get_temp_dir(), 're7la_tts_');
        file_put_contents($tmpFile, $texte);

        $pythonScript = sprintf(
            "from gtts import gTTS; tts = gTTS(text=open('%s', encoding='utf-8').read(), lang='%s', slow=False); tts.save('%s'); print('OK')",
            str_replace('\\', '/', $tmpFile),
            $langCode,
            str_replace('\\', '/', $audioPath)
        );

        foreach (['python3', 'python', 'py'] as $python) {
            try {
                $process = new Process([$python, '-c', $pythonScript]);
                $process->setTimeout(30);
                $process->run();

                if ($process->isSuccessful() && file_exists($audioPath) && filesize($audioPath) > 1000) {
                    @unlink($tmpFile);
                    return '/audio/tours/' . $audioFile;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        @unlink($tmpFile);
        return null;
    }

    // ══════════════════════════════════════════════════
    //  HELPER — Contexte culturel
    // ══════════════════════════════════════════════════

    private function getContexte(string $lieu, string $langue = 'français'): string
{
    $lieuLower = strtolower(trim($lieu));

    // Lieux connus en statique
    foreach ($this->contextes as $key => $contexte) {
        if (str_contains($lieuLower, $key)) {
            return $contexte;
        }
    }

    // ✅ Lieu inconnu (Mahdia, Gafsa, etc.) → l'IA génère le contexte, mis en cache 7 jours
    $cacheKey = 'contexte_' . md5($lieuLower);
    return $this->cache->get($cacheKey, function (ItemInterface $item) use ($lieu) {
        $item->expiresAfter(604800);

        $prompt = "Tu es expert en patrimoine tunisien. Donne-moi un paragraphe descriptif concis "
                . "(50-70 mots) sur {$lieu} en Tunisie : localisation, histoire, monuments principaux, "
                . "ce qui la distingue. Sans titre, texte direct.";

        try {
            $response = $this->client->request('POST',
                'https://api.groq.com/openai/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->groqApiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'       => 'llama-3.3-70b-versatile',
                        'messages'    => [['role' => 'user', 'content' => $prompt]],
                        'temperature' => 0.5,
                        'max_tokens'  => 200,
                    ]
                ]
            );
            $data = $response->toArray();
            return $data['choices'][0]['message']['content']
                ?? "Site touristique tunisien au riche patrimoine historique et culturel.";

        } catch (\Exception $e) {
            return "Site touristique tunisien au riche patrimoine historique et culturel.";
        }
    });
}
}