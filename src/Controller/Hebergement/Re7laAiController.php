<?php

namespace App\Controller\Hebergement;

use App\Service\Hebergement\GroqService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Re7laAiController extends AbstractController
{
    #[Route('/re7la-ai', name: 'app_re7la_ai')]
    public function index(): Response
    {
        return $this->render('frontend/re7la_ai/index.html.twig');
    }

    #[Route('/re7la-ai/chat', name: 'app_re7la_ai_chat', methods: ['POST'])]
    public function chat(Request $request, GroqService $groqService, \App\Repository\HebergementRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $messages = $data['messages'] ?? [];

        if (empty($messages)) {
            return new JsonResponse(['error' => 'No messages provided'], 400);
        }

        // Prepare context: Available accommodations
        $available = $repo->findAvailable();
        $context = "";
        foreach ($available as $h) {
            $context .= "- {$h->getTitre()} ({$h->getTypeHebergement()}): {$h->getPrixParNuit()} DT/nuit. Description: {$h->getDesc_hebergement()}\n";
        }

        $response = $groqService->getChatResponse($messages, $context);

        return new JsonResponse($response);
    }
}
