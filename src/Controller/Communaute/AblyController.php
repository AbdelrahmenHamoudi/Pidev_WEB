<?php

namespace App\Controller\Communaute;

use App\Service\AblyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AblyController extends AbstractController
{
    #[Route('/api/ably/token', name: 'api_ably_token', methods: ['POST'])]
    public function getToken(Request $request, AblyService $ablyService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $clientId = $data['clientId'] ?? null;
        
        if (!$ablyService->isConfigured()) {
            return $this->json(['error' => 'Ably not configured'], 500);
        }

        $capability = [
            'chat:*' => ['publish', 'subscribe', 'history', 'presence'],
            'chat:*:presence' => ['presence', 'subscribe'],
        ];

        $tokenRequest = $ablyService->generateTokenRequest($capability, $clientId);
        
        if (!$tokenRequest) {
            return $this->json(['error' => 'Failed to generate token'], 500);
        }

        return $this->json([
            'tokenRequest' => $tokenRequest,
            'clientId' => $clientId
        ]);
    }

    #[Route('/api/ably/history/{channel}', name: 'api_ably_history', methods: ['GET'])]
    public function getHistory(string $channel, AblyService $ablyService): JsonResponse
    {
        if (!$ablyService->isConfigured()) {
            return $this->json(['error' => 'Ably not configured'], 500);
        }

        $messages = $ablyService->getChannelHistory('chat:' . $channel, 100);
        
        return $this->json(['messages' => $messages]);
    }

    #[Route('/api/ably/config', name: 'api_ably_config', methods: ['GET'])]
    public function getConfig(AblyService $ablyService): JsonResponse
    {
        return $this->json([
            'configured' => $ablyService->isConfigured(),
            'environment' => $this->getParameter('kernel.environment')
        ]);
    }
}