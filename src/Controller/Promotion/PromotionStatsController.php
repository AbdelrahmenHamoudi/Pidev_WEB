<?php

namespace App\Controller\Promotion;

use App\Repository\PromotionRepository;
use App\Repository\ReservationPromoRepository;
use App\Service\Api2PdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PromotionStatsController extends AbstractController
{
    /**
     * Build QuickChart.io URL from a chart configuration array.
     * QuickChart is 100% free, requires no API key, and returns chart images.
     * Documentation: https://quickchart.io/documentation/
     */
    private function buildChartUrl(array $chartConfig, int $width = 400, int $height = 300): string
    {
        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($chartConfig))
            . '&w=' . $width . '&h=' . $height . '&bkg=white&f=png';
    }

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

        // ── QuickChart.io API: Generate chart image URLs ──
        $pieChartUrl = $this->buildChartUrl([
            'type' => 'pie',
            'data' => [
                'labels' => ['Individuelles', 'Packs'],
                'datasets' => [[
                    'data' => [$nbIndiv, $nbPacks],
                    'backgroundColor' => ['#3498DB', '#1ABC9C'],
                ]]
            ],
            'options' => [
                'plugins' => ['datalabels' => ['display' => true, 'color' => '#fff', 'font' => ['weight' => 'bold']]]
            ]
        ]);

        $barChartUrl = $this->buildChartUrl([
            'type' => 'bar',
            'data' => [
                'labels' => array_column($topPromos, 'name'),
                'datasets' => [[
                    'label' => 'Réservations',
                    'data' => array_column($topPromos, 'count'),
                    'backgroundColor' => '#F7B731',
                    'borderRadius' => 5,
                ]]
            ],
            'options' => [
                'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]
            ]
        ], 600, 300);

        $doughnutChartUrl = $this->buildChartUrl([
            'type' => 'doughnut',
            'data' => [
                'labels' => array_map('ucfirst', array_keys($typeDistribution)),
                'datasets' => [[
                    'data' => array_values($typeDistribution),
                    'backgroundColor' => ['#9b59b6', '#e74c3c', '#f1c40f'],
                ]]
            ],
            'options' => [
                'plugins' => ['datalabels' => ['display' => true, 'color' => '#fff', 'font' => ['weight' => 'bold']]]
            ]
        ]);

        return $this->render('backend/admin/promotion/stats.html.twig', [
            'pieData' => [$nbIndiv, $nbPacks],
            'topPromosLabels' => array_column($topPromos, 'name'),
            'topPromosData' => array_column($topPromos, 'count'),
            'typeDistLabels' => array_keys($typeDistribution),
            'typeDistData' => array_values($typeDistribution),
            'topUsers' => $topUsers,
            // QuickChart image URLs (for PDF embedding + fallback display)
            'pieChartUrl'      => $pieChartUrl,
            'barChartUrl'      => $barChartUrl,
            'doughnutChartUrl' => $doughnutChartUrl,
        ]);
    }

    // ════════════════════════════════════════════
    // EXPORT STATS AS PDF (via Api2Pdf + QuickChart images)
    // ════════════════════════════════════════════

    #[Route('/admin/promotion/stats/pdf', name: 'app_admin_promotion_stats_pdf')]
    public function exportStatsPdf(
        PromotionRepository $promoRepo,
        ReservationPromoRepository $resaRepo,
        Api2PdfService $api2PdfService
    ): Response {
        $promotions = $promoRepo->findAll();
        $reservations = $resaRepo->findAll();

        // Recompute stats (same as index)
        $nbPacks = 0;
        $nbIndiv = 0;
        foreach ($promotions as $p) {
            $p->isPack() ? $nbPacks++ : $nbIndiv++;
        }

        $promoStats = [];
        foreach ($promotions as $p) {
            $promoStats[$p->getId()] = ['name' => $p->getName(), 'count' => 0];
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

        $typeDistribution = ['hebergement' => 0, 'voiture' => 0, 'activite' => 0];
        foreach ($promotions as $p) {
            if (!$p->isPack() && $p->getOfferType() && isset($typeDistribution[$p->getOfferType()])) {
                $typeDistribution[$p->getOfferType()]++;
            }
        }

        $userStats = [];
        foreach ($reservations as $r) {
            if ($r->getUser() && $r->getStatut() !== 'ANNULEE') {
                $email = $r->getUser()->getE_mail() ?? 'Unknown';
                $userStats[$email] = ($userStats[$email] ?? 0) + 1;
            }
        }
        arsort($userStats);
        $topUsers = array_slice($userStats, 0, 5, true);

        // Generate QuickChart image URLs for the PDF
        $pieChartUrl = $this->buildChartUrl([
            'type' => 'pie',
            'data' => [
                'labels' => ['Individuelles (' . $nbIndiv . ')', 'Packs (' . $nbPacks . ')'],
                'datasets' => [['data' => [$nbIndiv, $nbPacks], 'backgroundColor' => ['#3498DB', '#1ABC9C']]]
            ]
        ]);

        $barChartUrl = $this->buildChartUrl([
            'type' => 'bar',
            'data' => [
                'labels' => array_column($topPromos, 'name'),
                'datasets' => [['label' => 'Réservations', 'data' => array_column($topPromos, 'count'), 'backgroundColor' => '#F7B731']]
            ],
            'options' => ['scales' => ['y' => ['beginAtZero' => true]]]
        ], 600, 300);

        $doughnutChartUrl = $this->buildChartUrl([
            'type' => 'doughnut',
            'data' => [
                'labels' => array_map('ucfirst', array_keys($typeDistribution)),
                'datasets' => [['data' => array_values($typeDistribution), 'backgroundColor' => ['#9b59b6', '#e74c3c', '#f1c40f']]]
            ]
        ]);

        $html = $this->renderView('backend/admin/promotion/stats_pdf.html.twig', [
            'pieChartUrl'      => $pieChartUrl,
            'barChartUrl'      => $barChartUrl,
            'doughnutChartUrl' => $doughnutChartUrl,
            'topUsers'         => $topUsers,
            'totalPromos'      => count($promotions),
            'nbIndiv'          => $nbIndiv,
            'nbPacks'          => $nbPacks,
            'totalReservations'=> count($reservations),
        ]);

        $result = $api2PdfService->htmlToPdf($html, 'statistiques_promotions.pdf');

        if ($result['success'] && $result['pdf_url']) {
            $pdfContent = $api2PdfService->downloadPdf($result['pdf_url']);
            if ($pdfContent) {
                return new Response($pdfContent, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="statistiques_promotions.pdf"',
                ]);
            }
        }

        $this->addFlash('error', 'Erreur PDF: ' . ($result['error'] ?? 'Erreur inconnue'));
        return $this->redirectToRoute('app_admin_promotion_stats');
    }
}
