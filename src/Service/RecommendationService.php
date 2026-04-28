<?php

namespace App\Service;

use App\Entity\Promotion;
use App\Repository\PromotionRepository;
use App\Repository\ReservationPromoRepository;

class RecommendationService
{
    public function __construct(
        private PromotionRepository $promoRepo,
        private ReservationPromoRepository $resaRepo,
        private GeminiService $geminiService
    ) {}

    /**
     * @return array<array{name: string, description: string, destination: string, promotionIds: int[], items: array<array{id: int, name: string, type: string}>}>
     */
    public function generateRecommendations(): array
    {
        // 1. Fetch individual promotions
        $promotions = $this->promoRepo->findBy(['promoType' => Promotion::TYPE_INDIVIDUELLE]);
        
        if (count($promotions) < 2) {
            throw new \Exception("Pas assez de promotions individuelles actives. Il en faut au moins 2 pour générer un pack.");
        }

        // 2. Prepare data for AI
        $promoData = [];
        foreach ($promotions as $p) {
            $resas = $this->resaRepo->findBy(['promotion' => $p]);
            $promoData[] = [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'description' => $p->getDescription(),
                'discount' => $p->getReductionLabel(),
                'views' => $p->getViews(),
                'reservations' => count($resas),
                'offerType' => $p->getOfferTypeLabel()
            ];
        }

        // 3. Build Prompt
        $prompt = "You are an expert travel marketing AI. I will provide you with a list of active individual promotions. 
        Your task is to create 'Promotion Packs' (bundles) by grouping 2 or 3 promotions that belong to the SAME destination (e.g., Djerba, Hammamet, Sousse, Tunis, etc.).
        
        Rules:
        1. Only use existing promotion IDs from the list.
        2. A pack must have 2 or 3 items.
        3. Items in a pack MUST be in the same location (infer location from name or description).
        4. Prioritize promotions with high views or reservations.
        5. Provide a catchy Name and Description for each recommended pack.
        6. Return the result ONLY as a JSON array of objects with these keys: name, description, destination, promotionIds (array of ints).
        
        Promotions List:
        " . json_encode($promoData);

        // 4. Call Gemini
        try {
            $jsonResponse = $this->geminiService->generateContent($prompt);
        } catch (\Exception $e) {
            throw new \Exception("Erreur de l'API Gemini : " . $e->getMessage());
        }
        
        if (!$jsonResponse) {
            throw new \Exception("L'API Gemini n'a renvoyé aucune réponse. Veuillez vérifier votre clé API.");
        }

        // Clean JSON if Gemini wrapped it in markdown
        $jsonResponse = preg_replace('/^```json\s*|\s*```$/i', '', trim($jsonResponse));

        $recommendations = json_decode($jsonResponse, true);
        if (!is_array($recommendations)) {
            throw new \Exception("Le format de réponse de l'IA est invalide. Impossible de parser le JSON.");
        }

        // 5. Enrich recommendations with item details (names)
        foreach ($recommendations as &$rec) {
            $rec['items'] = [];
            foreach ($rec['promotionIds'] as $pid) {
                $p = $this->promoRepo->find($pid);
                if ($p) {
                    $rec['items'][] = [
                        'id' => $p->getId(),
                        'name' => $p->getName(),
                        'type' => $p->getOfferType() ?? 'unknown'
                    ];
                }
            }
        }

        return $recommendations;
    }
}
