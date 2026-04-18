<?php

namespace App\Controller\Promotion;

use App\Entity\Promotion;
use App\Repository\PromotionRepository;
use App\Service\RecommendationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\HttpFoundation\Request;

#[Route('/admin/promotion/recommendations')]
class AiRecommendationController extends AbstractController
{
    #[Route('', name: 'app_admin_promotion_recommendations')]
    public function index(Request $request): Response
    {
        $recommendations = $request->getSession()->get('ai_recommendations', []);
        
        return $this->render('backend/admin/promotion/recommendations.html.twig', [
            'recommendations' => $recommendations
        ]);
    }

    #[Route('/generate', name: 'app_admin_promotion_recommendations_generate', methods: ['POST'])]
    public function generate(RecommendationService $recService, Request $request): Response
    {
        $recommendations = $recService->generateRecommendations();
        
        if (count($recommendations) > 0) {
            // Assign temporary IDs (indices) to recommendations for approval/rejection
            foreach ($recommendations as $idx => &$rec) {
                $rec['temp_id'] = $idx;
            }
            $request->getSession()->set('ai_recommendations', $recommendations);
            $this->addFlash('success', sprintf('%d nouvelles recommandations générées par l\'IA.', count($recommendations)));
        } else {
            $this->addFlash('warning', 'Aucune nouvelle recommandation n\'a pu être générée.');
        }

        return $this->redirectToRoute('app_admin_promotion_recommendations');
    }

    #[Route('/{tempId}/approve', name: 'app_admin_promotion_recommendations_approve', methods: ['POST'])]
    public function approve(
        int $tempId,
        Request $request,
        EntityManagerInterface $em,
        PromotionRepository $promoRepo
    ): Response {
        $recommendations = $request->getSession()->get('ai_recommendations', []);
        
        if (!isset($recommendations[$tempId])) {
            $this->addFlash('error', 'Recommandation introuvable.');
            return $this->redirectToRoute('app_admin_promotion_recommendations');
        }

        $rec = $recommendations[$tempId];

        // 1. Create a real Promotion Pack
        $promotion = new Promotion();
        $promotion->setName($rec['name']);
        $promotion->setDescription($rec['description'] . "\n\n(Recommandé par l'IA pour la destination : " . $rec['destination'] . ")");
        $promotion->setPromoType(Promotion::TYPE_PACK);
        $promotion->setStartDate(new \DateTime());
        $promotion->setEndDate((new \DateTime())->modify('+1 month'));
        
        // Fetch items details to build packItems JSON
        $items = [];
        $totalDiscount = 0;
        foreach ($rec['promotionIds'] as $pid) {
            $p = $promoRepo->find($pid);
            if ($p) {
                $items[] = [
                    'id' => $p->getId(),
                    'label' => $p->getName(), // FIXED: using 'label' to match show.html.twig
                    'type' => $p->getOfferType() ?? 'unknown'
                ];
                $totalDiscount += $p->getDiscountPercentage() ?? 5;
            }
        }
        
        $promotion->setPackItems(json_encode($items));
        $promotion->setDiscountPercentage(min(80, round(($totalDiscount / max(1, count($items))) + 5)));

        $em->persist($promotion);

        // 2. Remove from session
        unset($recommendations[$tempId]);
        $request->getSession()->set('ai_recommendations', $recommendations);
        
        $em->flush();

        $this->addFlash('success', 'La recommandation a été approuvée et publiée.');
        return $this->redirectToRoute('app_admin_promotion_recommendations');
    }

    #[Route('/{tempId}/reject', name: 'app_admin_promotion_recommendations_reject', methods: ['POST'])]
    public function reject(int $tempId, Request $request): Response
    {
        $recommendations = $request->getSession()->get('ai_recommendations', []);
        
        if (isset($recommendations[$tempId])) {
            unset($recommendations[$tempId]);
            $request->getSession()->set('ai_recommendations', $recommendations);
            $this->addFlash('info', 'Recommandation rejetée.');
        }

        return $this->redirectToRoute('app_admin_promotion_recommendations');
    }
}
