<?php

namespace App\Controller\Communaute;

use App\Service\CommunauteChatbotService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ChatbotController extends AbstractController
{
    private $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    #[Route('/api/chatbot/message', name: 'api_chatbot_message', methods: ['POST'])]
    public function sendMessage(Request $request, CommunauteChatbotService $chatbot): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $message = trim($data['message'] ?? '');
            
            if (empty($message)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Message vide'
                ], 400);
            }
            
            if (strlen($message) > 500) {
                return $this->json([
                    'success' => false,
                    'error' => 'Message trop long (max 500 caractères)'
                ], 400);
            }
            
            $this->logger->info('Chatbot request: ' . $message);
            
            $result = $chatbot->processMessage($message);
            
            $this->logger->info('Chatbot response category: ' . ($result['category'] ?? 'unknown'));
            
            return $this->json([
                'success' => $result['success'],
                'response' => $result['response'],
                'category' => $result['category'] ?? 'unknown',
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Chatbot error: ' . $e->getMessage());
            $this->logger->error($e->getTraceAsString());
            
            return $this->json([
                'success' => false,
                'response' => "Désolé, une erreur est survenue. Veuillez réessayer dans quelques instants.",
                'category' => 'error'
            ], 200); // Return 200 so frontend still processes it
        }
    }
    
    #[Route('/api/chatbot/suggestions', name: 'api_chatbot_suggestions', methods: ['GET'])]
    public function getSuggestions(CommunauteChatbotService $chatbot): JsonResponse
    {
        try {
            return $this->json([
                'suggestions' => $chatbot->getSuggestedQuestions(),
                'welcome' => $chatbot->getWelcomeMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Suggestions error: ' . $e->getMessage());
            
            return $this->json([
                'suggestions' => [
                    '📝 Comment créer une publication ?',
                    '🏷 Quelles sont les catégories ?',
                    '💬 Comment fonctionnent les commentaires ?'
                ],
                'welcome' => "Bonjour ! Je suis l'assistant de RE7LA. Comment puis-je vous aider ?"
            ]);
        }
    }
    
    #[Route('/api/chatbot/status', name: 'api_chatbot_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $apiToken = $_ENV['HUGGINGFACE_API_TOKEN'] ?? null;
        
        return $this->json([
            'configured' => !empty($apiToken),
            'model' => 'meta-llama/Llama-3.1-8B-Instruct',
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }
}