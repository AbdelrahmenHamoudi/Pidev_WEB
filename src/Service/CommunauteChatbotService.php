<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class CommunauteChatbotService
{
    private $httpClient;
    private $logger;
    private $apiToken;
    private $apiUrl;
    
    public function __construct(
        HttpClientInterface $httpClient, 
        LoggerInterface $logger,
        string $huggingfaceApiToken = null
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiToken = $huggingfaceApiToken ?: ($_ENV['HUGGINGFACE_API_TOKEN'] ?? null);
        $this->apiUrl = 'https://api-inference.huggingface.co/models/';
    }
    
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
        
        // Create a proper chat template for Llama 3.1
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
                'timeout' => 60, // Llama can take a bit longer
            ]);
            
            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);
            
            $this->logger->info('Llama API response status: ' . $statusCode);
            
            $generatedText = '';
            
            // Handle different response formats
            if (isset($content[0]['generated_text'])) {
                $generatedText = $content[0]['generated_text'];
            } elseif (isset($content['generated_text'])) {
                $generatedText = $content['generated_text'];
            } elseif (is_string($content)) {
                $generatedText = $content;
            } elseif (isset($content['error'])) {
                $this->logger->error('Llama API error: ' . json_encode($content));
                throw new \Exception('API returned error: ' . ($content['error'] ?? 'Unknown error'));
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
            
            // Check if model is loading
            if (strpos($e->getMessage(), 'loading') !== false) {
                return "Le modèle est en cours de chargement. Veuillez réessayer dans quelques secondes.";
            }
            
            throw $e;
        }
    }
    
    private function buildLlamaPrompt(string $message): string
    {
        // Llama 3.1 Instruct format
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
        
        // Format specifically for Llama 3.1 Instruct
        $prompt = "<|begin_of_text|><|start_header_id|>system<|end_header_id|>\n\n";
        $prompt .= $systemPrompt;
        $prompt .= "<|eot_id|><|start_header_id|>user<|end_header_id|>\n\n";
        $prompt .= $message;
        $prompt .= "<|eot_id|><|start_header_id|>assistant<|end_header_id|>\n\n";
        
        return $prompt;
    }
    
    private function cleanResponse(string $text): string
    {
        // Remove any special tokens that might leak through
        $text = preg_replace('/<\|.*?\|>/', '', $text);
        
        // Remove the prompt if it's echoed back
        $text = preg_replace('/^.*?assistant[|>]\s*/s', '', $text);
        
        // Trim and clean up
        $text = trim($text);
        
        // Remove multiple newlines
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        
        // Limit response length
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
    
    private function extractTextFromResponse(array $content): string
    {
        // Recursively search for text in response
        $text = '';
        array_walk_recursive($content, function($value) use (&$text) {
            if (is_string($value) && strlen($value) > strlen($text)) {
                $text = $value;
            }
        });
        return $text;
    }
    
    private function getFallbackResponse(string $message): array
    {
        $message = strtolower(trim($message));
        
        // Pattern matching fallback for when API is unavailable
        if (strpos($message, 'bonjour') !== false || strpos($message, 'salut') !== false) {
            $response = "Bonjour ! Je suis l'assistant virtuel de RE7LA, propulsé par Llama 3.1. Comment puis-je vous aider aujourd'hui ?";
        } elseif (strpos($message, 'publication') !== false || strpos($message, 'publier') !== false) {
            $response = "Pour créer une publication sur RE7LA :\n1. Cliquez sur 'Nouvelle publication'\n2. Choisissez une catégorie (Hébergement, Activité, ou Transport)\n3. Ajoutez une description (200 caractères max)\n4. Téléchargez une image\n5. Soumettez pour validation\n\nVotre publication sera visible après validation par notre équipe !";
        } elseif (strpos($message, 'catégorie') !== false || strpos($message, 'type') !== false) {
            $response = "RE7LA propose 3 catégories de publications :\n\n🏨 Hébergement - Partagez vos bons plans logement\n🏄 Activité - Parlez de vos expériences et loisirs\n🚌 Transport - Conseils pour se déplacer\n\nUtilisez les filtres pour explorer chaque catégorie !";
        } elseif (strpos($message, 'commentaire') !== false || strpos($message, 'commenter') !== false) {
            $response = "Les commentaires vous permettent d'échanger avec la communauté !\n• Commentez n'importe quelle publication\n• Maximum 200 caractères\n• Vous pouvez modifier ou supprimer vos propres commentaires\n\nN'hésitez pas à partager votre avis et vos questions !";
        } elseif (strpos($message, 'règle') !== false || strpos($message, 'règlement') !== false) {
            $response = "Les règles de la communauté RE7LA :\n✅ Soyez respectueux et bienveillant\n✅ Contenu en rapport avec le voyage\n✅ Pas de spam ou publicité\n❌ Pas de contenu offensant\n❌ Pas d'informations personnelles\n\nMerci de contribuer à une communauté positive !";
        } elseif (strpos($message, 'aide') !== false || strpos($message, 'help') !== false) {
            $response = "Je peux vous aider avec :\n• La création de publications\n• Les catégories disponibles\n• Le système de commentaires\n• Les règles de la communauté\n• La recherche de contenu\n\nQue souhaitez-vous savoir ?";
        } elseif (strpos($message, 'qui') !== false && strpos($message, 'tu') !== false) {
            $response = "Je suis l'assistant virtuel de RE7LA, propulsé par l'intelligence artificielle Llama 3.1 de Meta. Je suis là pour vous aider à naviguer dans la communauté, répondre à vos questions, et vous guider dans l'utilisation de la plateforme. Enchanté de faire votre connaissance ! 😊";
        } else {
            $response = "Je suis l'assistant IA de RE7LA. Je peux vous renseigner sur :\n• Comment créer une publication\n• Les catégories disponibles\n• Le fonctionnement des commentaires\n• Les règles de la communauté\n• Et bien plus !\n\nPosez-moi votre question et je ferai de mon mieux pour vous aider !";
        }
        
        return [
            'success' => true,
            'response' => $response,
            'category' => 'fallback'
        ];
    }
    
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