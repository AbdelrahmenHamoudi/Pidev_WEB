<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaValidator
{
    private HttpClientInterface $client;
    private string $secret;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $client, string $secret, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->secret = $secret;
        $this->logger = $logger;
    }

    /**
     * Vérifie un token reCAPTCHA v2.
     * Retourne true si valide, false sinon.
     */
    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        if (!$token) {
            $this->logger->error('reCAPTCHA: Token vide ou null');
            return false;
        }

        try {
            $response = $this->client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $this->secret,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray(false);

            $this->logger->info('reCAPTCHA response', $data);

            if (!isset($data['success']) || $data['success'] !== true) {
                $this->logger->error('reCAPTCHA échoué', [
                    'error-codes' => $data['error-codes'] ?? []
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('reCAPTCHA exception: ' . $e->getMessage());
            return false;
        }
    }
}