<?php

namespace App\Controller\Promotion;

use App\Repository\PromotionRepository;
use App\Repository\ReservationPromoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PromotionStatsController extends AbstractController
{
    #[Route('/admin/promotion/stats', name: 'app_admin_promotion_stats')]
    public function index(PromotionRepository $promoRepo, ReservationPromoRepository $resaRepo): Response
    {
        $promotions = $promoRepo->findAll();
        $reservations = $resaRepo->findAll();

        // 1. Pack vs Individuelle
        $nbPacks = 0;
        $nbIndiv = 0;
        foreach ($promotions as $p) {
            if ($p->isPack()) {
                $nbPacks++;
            } else {
                $nbIndiv++;
            }
        }

        // 2. Top 10 promotions by reservations
        $promoStats = [];
        foreach ($promotions as $p) {
            $promoStats[$p->getId()] = [
                'name' => $p->getName(),
                'count' => 0
            ];
        }
        
        foreach ($reservations as $r) {
            if ($r->getPromotion() && $r->getStatut() !== 'ANNULEE') {
                $id = $r->getPromotion()->getId();
                if (isset($promoStats[$id])) {
                    $promoStats[$id]['count']++;
                }
            }
        }

        usort($promoStats, fn($a, $b) => $b['count'] <=> $a['count']);
        $topPromos = array_slice($promoStats, 0, 10);

        // 3. Distribution by Type (Hebergement, Voiture, Activite) for Individuals
        $typeDistribution = ['hebergement' => 0, 'voiture' => 0, 'activite' => 0];
        foreach ($promotions as $p) {
            if (!$p->isPack() && $p->getOfferType()) {
                $type = $p->getOfferType();
                if (isset($typeDistribution[$type])) {
                    $typeDistribution[$type]++;
                }
            }
        }

        // 4. Most active users
        $userStats = [];
        foreach ($reservations as $r) {
            if ($r->getUser() && $r->getStatut() !== 'ANNULEE') {
                $email = $r->getUser()->getE_mail() ?? 'Unknown';
                if (!isset($userStats[$email])) {
                    $userStats[$email] = 0;
                }
                $userStats[$email]++;
            }
        }
        arsort($userStats);
        $topUsers = array_slice($userStats, 0, 5, true);

        return $this->render('backend/admin/promotion/stats.html.twig', [
            'pieData' => [$nbIndiv, $nbPacks],
            'topPromosLabels' => array_column($topPromos, 'name'),
            'topPromosData' => array_column($topPromos, 'count'),
            'typeDistLabels' => array_keys($typeDistribution),
            'typeDistData' => array_values($typeDistribution),
            'topUsers' => $topUsers,
        ]);
    }
}
