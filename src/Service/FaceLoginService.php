<?php

namespace App\Service;

class FaceLoginService
{
    private string $baseUrl;

    public function __construct(string $faceApiUrl = 'http://127.0.0.1:5000')
    {
        $this->baseUrl = $faceApiUrl;
    }

    /**
     * Verifier si le serveur Flask est en ligne
     */
    public function isServerRunning(): bool
    {
        try {
            $context = stream_context_create([
                'http' => ['timeout' => 3, 'method' => 'GET'],
            ]);
            $response = @file_get_contents($this->baseUrl . '/health', false, $context);
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Enregistrer un visage (email = username dans Flask)
     */
    public function registerFace(string $email, string $imageBase64): array
    {
        return $this->postJson('/register_face', [
            'username' => $email,
            'image' => $imageBase64,
        ]);
    }

    /**
     * Login par reconnaissance faciale
     */
    public function loginWithFace(string $imageBase64): array
    {
        return $this->postJson('/login_face', [
            'image' => $imageBase64,
        ]);
    }

    /**
     * Desactiver Face ID pour un utilisateur
     */
    public function disableFace(string $email): array
    {
        return $this->postJson('/disable_face', [
            'username' => $email,
        ]);
    }

    /**
     * Verifier si un utilisateur a Face ID active
     */
    public function checkFace(string $email): bool
    {
        $result = $this->postJson('/check_face', [
            'username' => $email,
        ]);

        return ($result['success'] ?? false) && ($result['enabled'] ?? false);
    }

    /**
     * Appel POST JSON vers Flask
     */
    private function postJson(string $endpoint, array $data): array
    {
        $default = ['success' => false, 'message' => 'Serveur Face ID indisponible'];
        try {
            $json = json_encode($data);
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nContent-Length: " . strlen($json) . "\r\n",
                    'content' => $json,
                    'ignore_errors' => true,
                ],
            ]);
            $response = @file_get_contents($this->baseUrl . $endpoint, false, $context);
            if ($response === false) {
                return $default;
            }
            $result = json_decode($response, true);
            return $result ?: $default;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}