<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $geminiApiKey
    ) {}

    public function generateContent(string $prompt): ?string
    {
        if (empty($this->geminiApiKey) || $this->geminiApiKey === 'your_gemini_api_key_here') {
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', self::API_URL . '?key=' . $this->geminiApiKey, [
                'verify_peer' => false,
                'verify_host' => false,
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                    ]
                ]
            ]);

            $data = $response->toArray();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        } catch (\Exception $e) {
            // Log error in a real app
            return null;
        }
    }
}
