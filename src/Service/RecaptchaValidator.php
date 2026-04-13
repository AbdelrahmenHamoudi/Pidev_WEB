<?php
// src/Service/RecaptchaValidator.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaValidator
{
    private HttpClientInterface $client;
    private string $secret;

    public function __construct(HttpClientInterface $client, string $secret)
    {
        $this->client = $client;
        $this->secret = $secret;
    }

    /**
     * Vérifie un token reCAPTCHA v2.
     * Retourne true si valide, false sinon.
     */
    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        if (!$token) {
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
        } catch (\Throwable $e) {
            // Si l'API est indisponible, retourne false (ou true selon tolérance)
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $data = $response->toArray(false);
        return isset($data['success']) && $data['success'] === true;
    }
}