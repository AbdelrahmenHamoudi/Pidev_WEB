<?php

namespace App\Service\Hebergement;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TranslationService
{
    private const API_URL = 'https://libretranslate.de/translate';
    private const CACHE_EXPIRE = 86400 * 7; // 1 week

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache
    ) {}

    /**
     * Translates a text using LibreTranslate API.
     */
    public function translate(string $text, string $targetLocale, string $sourceLocale = 'auto'): string
    {
        if (empty($text) || $targetLocale === $sourceLocale) return $text;

        $cacheKey = 'trans_' . md5($text . $targetLocale);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($text, $targetLocale, $sourceLocale) {
            $item->expiresAfter(self::CACHE_EXPIRE);
            
            try {
                $response = $this->httpClient->request('POST', self::API_URL, [
                    'json' => [
                        'q' => $text,
                        'source' => $sourceLocale,
                        'target' => $targetLocale,
                        'format' => 'text'
                    ]
                ]);
                
                $data = $response->toArray();
                return $data['translatedText'] ?? $text;
            } catch (\Exception $e) {
                return $text;
            }
        });
    }
}
