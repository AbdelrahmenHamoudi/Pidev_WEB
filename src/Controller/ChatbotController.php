<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/chatbot')]
class ChatbotController extends AbstractController
{
    private const TRAVEL_DATA = [
        'tunis' => [
            'description' => 'La capitale de la Tunisie, connue pour sa Médina et le musée du Bardo.',
            'distances' => ['sousse' => '140 km', 'hammamet' => '65 km', 'sfax' => '270 km', 'djerba' => '500 km']
        ],
        'sousse' => [
            'description' => 'La perle du Sahel, célèbre pour ses plages et son Ribat.',
            'distances' => ['tunis' => '140 km', 'monastir' => '20 km', 'sfax' => '130 km']
        ],
        'hammamet' => [
            'description' => 'Une destination touristique majeure connue pour son jasmin et ses plages.',
            'distances' => ['tunis' => '65 km', 'sousse' => '90 km']
        ],
        'sfax' => [
            'description' => 'La capitale économique de la Tunisie.',
            'distances' => ['tunis' => '270 km', 'sousse' => '130 km', 'gabes' => '140 km']
        ],
        'djerba' => [
            'description' => 'L\'île des rêves, célèbre pour son climat et ses plages de sable blanc.',
            'distances' => ['tunis' => '500 km', 'gabes' => '110 km']
        ],
        'bizerte' => [
            'description' => 'Connue pour son vieux port et ses plages magnifiques au nord.',
            'distances' => ['tunis' => '65 km']
        ]
    ];

    #[Route('/ask', name: 'api_chatbot_ask', methods: ['POST'])]
    public function ask(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $question = strtolower($data['question'] ?? '');

        if (empty($question)) {
            return new JsonResponse(['error' => 'Question vide'], 400);
        }

        $response = $this->generateResponse($question);

        return new JsonResponse($response);
    }

    private function generateResponse(string $question): array
    {
        // Salutations
        if (preg_match('/(salut|bonjour|hi|hello|coucou)/', $question)) {
            return [
                'text' => "Bonjour ! Je suis votre assistant RE7LA. Posez-moi une question sur vos trajets en Tunisie.",
                'speech' => "Bonjour ! Je suis votre assistant Ré-h-la. Posez-moi une question sur vos trajets en Tunisie."
            ];
        }

        // Questions sur les distances
        if (preg_match('/(distance|combien de km|combien de route)/', $question)) {
            foreach (self::TRAVEL_DATA as $city => $info) {
                if (strpos($question, $city) !== false) {
                    foreach ($info['distances'] as $dest => $dist) {
                        if (strpos($question, $dest) !== false) {
                            return [
                                'text' => "La distance entre " . ucfirst($city) . " et " . ucfirst($dest) . " est d'environ " . $dist . ".",
                                'speech' => "La distance entre " . $city . " et " . $dest . " est d'environ " . $dist . "."
                            ];
                        }
                    }
                }
            }
            return [
                'text' => "Je connais les distances entre les grandes villes comme Tunis, Sousse, Hammamet, Sfax et Djerba. Précisez deux villes !",
                'speech' => "Je connais les distances entre les grandes villes comme Tunis, Sousse, Hammamet, Sfax et Djerba. Précisez deux villes !"
            ];
        }

        // Questions informatives sur les villes
        foreach (self::TRAVEL_DATA as $city => $info) {
            if (strpos($question, $city) !== false) {
                return [
                    'text' => ucfirst($city) . " : " . $info['description'],
                    'speech' => $city . " est " . $info['description']
                ];
            }
        }

        // Aide
        if (preg_match('/(aide|help|quoi faire|comment)/', $question)) {
            return [
                'text' => "Je peux vous renseigner sur les distances entre les villes tunisiennes ou vous donner des informations sur les destinations. Par exemple : 'Quelle est la distance entre Tunis et Sousse ?'",
                'speech' => "Je peux vous renseigner sur les distances entre les villes tunisiennes ou vous donner des informations sur les destinations."
            ];
        }

        // Défaut
        return [
            'text' => "Je ne suis pas sûr de comprendre. Je suis un expert des trajets en Tunisie ! Essayez de me demander la distance entre deux villes.",
            'speech' => "Je ne suis pas sûr de comprendre. Essayez de me demander la distance entre deux villes."
        ];
    }
}
