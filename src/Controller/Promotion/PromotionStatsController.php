<?php

namespace App\Controller\Promotion;

use App\Repository\PromotionRepository;
use App\Repository\ReservationPromoRepository;
use App\Service\Api2PdfService;
use App\Entity\ReservationPromo;
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
    /** @param array<string, mixed> $chartConfig */
    private function buildChartUrl(array $chartConfig, int $width = 400, int $height = 300): string
    {
        $json = json_encode($chartConfig);
        return 'https://quickchart.io/chart?c=' . urlencode($json ?: '')
            . '&w=' . $width . '&h=' . $height . '&bkg=white&f=png';
    }

    #[Route('/admin/promotion/stats', name: 'app_admin_promotion_stats')]
    public function index(PromotionRepository $promoRepo, ReservationPromoRepository $resaRepo): Response
    {
        /** @var \App\Entity\Promotion[] $promotions */
        $promotions = $promoRepo->findAll();
        /** @var \App\Entity\ReservationPromo[] $reservations */
        $reservations = $resaRepo->findAll();

        // 1. Distribution by Type (Count)
        $typeDistribution = ['hébergement' => 0, 'voiture' => 0, 'activité' => 0, 'pack' => 0];
        foreach ($promotions as $p) {
            $type = $p->isPack() ? 'pack' : strtolower($p->getOfferType() ?? 'unknown');
            if (isset($typeDistribution[$type])) {
                $typeDistribution[$type]++;
            }
        }

        // 2. Top Promotions (Reservations count)
        $promoStats = [];
        foreach ($promotions as $p) {
            /** @var \App\Entity\ReservationPromo[] $resas */
            $resas = $resaRepo->findBy(['promotion' => $p]);
            $count = count(array_filter($resas, fn($r) => $r->getStatut() !== \App\Entity\ReservationPromo::STATUT_ANNULEE));
            $promoStats[] = [
                'name' => $p->getName(),
                'count' => $count,
                'isPack' => $p->isPack()
            ];
        }
        
        // Sort for Top Promotions
        usort($promoStats, fn($a, $b) => $b['count'] <=> $a['count']);
        $topPromos = array_slice($promoStats, 0, 10);

        // Filter for Best Pack Combos (Only packs)
        $packStats = array_filter($promoStats, fn($s) => $s['isPack']);
        $bestPacks = array_slice($packStats, 0, 5);

        // 3. User Engagement
        $userCounts = [];
        foreach ($reservations as $r) {
            if ($r->getUser() && $r->getStatut() !== \App\Entity\ReservationPromo::STATUT_ANNULEE) {
                $email = $r->getUser()->getE_mail() ?? 'Client #' . $r->getUser()->getId();
                $userCounts[$email] = ($userCounts[$email] ?? 0) + 1;
            }
        }
        arsort($userCounts);
        $topUsers = array_slice($userCounts, 0, 5, true);
        
        asort($userCounts);
        $leastUsers = array_slice($userCounts, 0, 5, true);

        // 4. Global Stats
        $totalReservations = count(array_filter($reservations, fn($r) => $r->getStatut() !== \App\Entity\ReservationPromo::STATUT_ANNULEE));
        $totalViews = 0;
        foreach ($promotions as $p) {
            $totalViews += $p->getViews();
        }
        $engagementRate = $totalViews > 0 ? ($totalReservations / $totalViews) * 100 : 0;

        // 5. Smart Engine & AI Impact
        $aiGeneratedCount = count(array_filter($promotions, fn($p) => $p->isAiGenerated()));
        $smartAdjustedCount = count(array_filter($promotions, fn($p) => $p->getAutoDiscountReason() !== null));

        return $this->render('backend/admin/promotion/stats.html.twig', [
            'typeDistLabels' => array_keys($typeDistribution),
            'typeDistData' => array_values($typeDistribution),
            
            'topPromosLabels' => array_column($topPromos, 'name'),
            'topPromosData' => array_column($topPromos, 'count'),
            
            'bestPacks' => $bestPacks,
            'topUsers' => $topUsers,
            'leastUsers' => $leastUsers,

            'totalPromos' => count($promotions),
            'totalReservations' => $totalReservations,
            'totalViews' => $totalViews,
            'engagementRate' => round($engagementRate, 1),
            'aiGeneratedCount' => $aiGeneratedCount,
            'smartAdjustedCount' => $smartAdjustedCount
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
        /** @var \App\Entity\Promotion[] $promotions */
        $promotions = $promoRepo->findAll();
        /** @var \App\Entity\ReservationPromo[] $reservations */
        $reservations = $resaRepo->findAll();

        // Recompute stats (same as index)
        $nbPacks = 0;
        $nbIndiv = 0;
        foreach ($promotions as $p) {
            $p->isPack() ? $nbPacks++ : $nbIndiv++;
        }

        $promoStats = [];
        foreach ($promotions as $p) {
            $promoStats[(int) $p->getId()] = ['name' => $p->getName(), 'count' => 0];
        }
        foreach ($reservations as $r) {
            if ($r->getPromotion() && $r->getStatut() !== 'ANNULEE') {
                $id = (int) $r->getPromotion()->getId();
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

        // 4. Global Stats
        $totalViews = 0;
        foreach ($promotions as $p) {
            $totalViews += $p->getViews();
        }
        $engagementRate = $totalViews > 0 ? (count($reservations) / $totalViews) * 100 : 0;

        $html = $this->renderView('backend/admin/promotion/stats_pdf.html.twig', [
            'pieChartUrl'      => $pieChartUrl,
            'barChartUrl'      => $barChartUrl,
            'doughnutChartUrl' => $doughnutChartUrl,
            'topUsers'         => $topUsers,
            'totalPromos'      => count($promotions),
            'nbIndiv'          => $nbIndiv,
            'nbPacks'          => $nbPacks,
            'totalReservations'=> count($reservations),
            'totalViews'       => $totalViews,
            'engagementRate'   => round($engagementRate, 1),
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
