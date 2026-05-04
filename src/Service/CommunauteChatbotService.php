<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class CommunauteChatbotService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private ?string $apiToken;
    private string $apiUrl;
    
    public function __construct(
        HttpClientInterface $httpClient, 
        LoggerInterface $logger,
        ?string $huggingfaceApiToken = null
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiToken = $huggingfaceApiToken ?: ($_ENV['HUGGINGFACE_API_TOKEN'] ?? null);
        $this->apiUrl = 'https://api-inference.huggingface.co/models/';
    }
    
    /**
     * @return array{success: bool, response: string, category: string}
     */
    public function processMessage(string $message): array
    {
        $this->logger->info('Processing message with Llama 3.1: ' . $message);
        
        if (!$this->apiToken) {
            $this->logger->warning('No Hugging Face API token configured');
            return $this->getFallbackResponse($message);
        }
        
        try {
            $response = $this->callLlamaAPI($message);
            
            return [
                'success' => true,
                'response' => $response,
                'category' => 'ai_response'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Llama API error: ' . $e->getMessage());
            return $this->getFallbackResponse($message);
        }
    }
    
    private function callLlamaAPI(string $message): string
    {
        $model = 'meta-llama/Llama-3.1-8B-Instruct';
        $prompt = $this->buildLlamaPrompt($message);
        
        $this->logger->info('Sending request to Llama 3.1 API');
        
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl . $model, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_new_tokens' => 256,
                        'temperature' => 0.7,
                        'top_p' => 0.9,
                        'do_sample' => true,
                        'return_full_text' => false,
                    ]
                ],
                'timeout' => 60,
            ]);
            
            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);
            
            $this->logger->info('Llama API response status: ' . $statusCode);
            
            $generatedText = '';
            
            if (isset($content[0]['generated_text'])) {
                $generatedText = $content[0]['generated_text'];
            } elseif (isset($content['generated_text'])) {
                $generatedText = $content['generated_text'];
            } elseif (isset($content['error'])) {
                $this->logger->error('Llama API error: ' . json_encode($content));
                throw new \Exception('API returned error: ' . $content['error']);
            } else {
                $this->logger->warning('Unexpected response format: ' . json_encode($content));
                $generatedText = $this->extractTextFromResponse($content);
            }
            
            $generatedText = $this->cleanResponse($generatedText);
            
            if (empty($generatedText)) {
                return "Je suis désolé, je n'ai pas pu générer une réponse. Pouvez-vous reformuler votre question ?";
            }
            
            return $generatedText;
            
        } catch (\Exception $e) {
            $this->logger->error('HTTP request failed: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'loading') !== false) {
                return "Le modèle est en cours de chargement. Veuillez réessayer dans quelques secondes.";
            }
            
            throw $e;
        }
    }
    
    private function buildLlamaPrompt(string $message): string
    {
        $systemPrompt = <<<EOT
Tu es l'assistant virtuel de RE7LA, une communauté de voyageurs tunisiens. 
La plateforme permet aux utilisateurs de :
- Partager des publications sur l'hébergement, les activités et les transports
- Commenter et interagir avec d'autres voyageurs
- Découvrir des bons plans de voyage

Directives importantes :
- Réponds TOUJOURS en français
- Sois amical, chaleureux et utile
- Garde tes réponses concises (3-5 phrases maximum)
- Si tu ne sais pas quelque chose, dis-le honnêtement
- Concentre-toi sur l'aide à la communauté RE7LA
EOT;
        
        $prompt = "<|begin_of_text|><|start_header_id|>system<|end_header_id|>\n\n";
        $prompt .= $systemPrompt;
        $prompt .= "<|eot_id|><|start_header_id|>user<|end_header_id|>\n\n";
        $prompt .= $message;
        $prompt .= "<|eot_id|><|start_header_id|>assistant<|end_header_id|>\n\n";
        
        return $prompt;
    }
    
    private function cleanResponse(?string $text): string
    {
        if ($text === null) {
            return '';
        }
        
        $text = (string) preg_replace('/<\|.*?\|>/', '', $text);
        $text = (string) preg_replace('/^.*?assistant[|>]\s*/s', '', $text);
        $text = trim($text);
        $text = (string) preg_replace('/\n\s*\n/', "\n\n", $text);
        
        if (strlen($text) > 500) {
            $sentences = explode('.', $text);
            $text = '';
            foreach ($sentences as $sentence) {
                if (strlen($text . $sentence) > 450) break;
                $text .= $sentence . '.';
            }
        }
        
        return $text;
    }
    
    /**
     * @param array<int|string, mixed> $content
     */
    private function extractTextFromResponse(array $content): string
    {
        $text = '';
        array_walk_recursive($content, function($value) use (&$text): void {
            if (is_string($value) && strlen($value) > strlen($text)) {
                $text = $value;
            }
        });
        return $text;
    }
    
    /**
     * @return array{success: bool, response: string, category: string}
     */
    private function getFallbackResponse(string $message): array
    {
        $message = strtolower(trim($message));
        
        $response = match(true) {
            strpos($message, 'bonjour') !== false || strpos($message, 'salut') !== false => 
                "Bonjour ! Je suis l'assistant virtuel de RE7LA, propulsé par Llama 3.1. Comment puis-je vous aider aujourd'hui ?",
            strpos($message, 'publication') !== false || strpos($message, 'publier') !== false => 
                "Pour créer une publication sur RE7LA :\n1. Cliquez sur 'Nouvelle publication'\n2. Choisissez une catégorie\n3. Ajoutez une description\n4. Téléchargez une image\n5. Soumettez pour validation",
            strpos($message, 'catégorie') !== false || strpos($message, 'type') !== false => 
                "RE7LA propose 3 catégories :\n🏨 Hébergement\n🏄 Activité\n🚌 Transport",
            strpos($message, 'commentaire') !== false || strpos($message, 'commenter') !== false => 
                "Les commentaires vous permettent d'échanger avec la communauté ! Maximum 200 caractères.",
            strpos($message, 'règle') !== false || strpos($message, 'règlement') !== false => 
                "Règles : Soyez respectueux, pas de spam, contenu voyage uniquement.",
            strpos($message, 'aide') !== false || strpos($message, 'help') !== false => 
                "Je peux vous aider avec les publications, catégories, commentaires et règles.",
            strpos($message, 'qui') !== false && strpos($message, 'tu') !== false => 
                "Je suis l'assistant virtuel de RE7LA, propulsé par Llama 3.1. Enchanté ! 😊",
            default => 
                "Je suis l'assistant IA de RE7LA. Posez-moi votre question sur la communauté !"
        };
        
        return [
            'success' => true,
            'response' => $response,
            'category' => 'fallback'
        ];
    }
    
    /**
     * @return array<int, string>
     */
    public function getSuggestedQuestions(): array
    {
        return [
            '📝 Comment créer une publication ?',
            '🏷 Quelles sont les catégories ?',
            '💬 Comment fonctionnent les commentaires ?',
            '📏 Quelles sont les règles ?',
            '🤖 Qui es-tu ?',
            '🔍 Comment rechercher du contenu ?'
        ];
    }
    
    public function getWelcomeMessage(): string
    {
        return "👋 Bonjour et bienvenue sur RE7LA !\n\n" .
               "Je suis votre assistant virtuel, propulsé par Llama 3.1 🦙\n\n" .
               "Je peux vous aider à :\n" .
               "• Découvrir comment créer des publications\n" .
               "• Comprendre les différentes catégories\n" .
               "• Apprendre à interagir avec la communauté\n" .
               "• Connaître les règles de RE7LA\n" .
               "• Répondre à toutes vos questions\n\n" .
               "Comment puis-je vous aider aujourd'hui ?";
    }
}