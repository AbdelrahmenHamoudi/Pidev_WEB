<?php
require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

(new Dotenv())->bootEnv(__DIR__ . '/.env');
$apiKey = $_ENV['GROQ_API_KEY'] ?? 'missing';
echo "Key begins with: " . substr($apiKey, 0, 5) . "\n";

$client = HttpClient::create();
$response = $client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
    'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
    ],
    'json' => [
        'model' => 'llama-3.1-8b-instant',
        'messages' => [['role' => 'user', 'content' => 'Return { "test": "ok" } in JSON format.']],
        'response_format' => ['type' => 'json_object']
    ]
]);

try {
    echo $response->getContent() . "\n";
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
    if (method_exists($e, 'getResponse')) {
        echo $e->getResponse()->getContent(false) . "\n";
    }
}
