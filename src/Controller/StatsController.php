<?php

namespace App\Controller;

use App\Repository\TrajetRepository;
use App\Repository\VoitureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/stats')]
class StatsController extends AbstractController
{
    #[Route('/dashboard', name: 'api_stats_dashboard', methods: ['GET'])]
    public function dashboard(TrajetRepository $trajetRepo, VoitureRepository $voitureRepo): JsonResponse
    {
        // --- VOITURE STATS ---
        $totalVoitures = $voitureRepo->count([]);
        $voituresDisponibles = $voitureRepo->count(['disponibilite' => true]);
        $voituresIndisponibles = $totalVoitures - $voituresDisponibles;

        // --- TRAJET STATS ---
        $totalTrajets = $trajetRepo->count([]);
        
        $statusCounts = $trajetRepo->createQueryBuilder('t')
            ->select('t.statut, COUNT(t.id_trajet) as count')
            ->groupBy('t.statut')
            ->getQuery()
            ->getResult();

        $statusData = [];
        foreach ($statusCounts as $sc) {
            $statusData[$sc['statut']] = (int)$sc['count'];
        }

        // --- DISTANCE STATS ---
        $distData = $trajetRepo->createQueryBuilder('t')
            ->select('SUM(t.distance_km) as total_dist, AVG(t.distance_km) as avg_dist')
            ->getQuery()
            ->getOneOrNullResult();

        // --- REVENUE ESTIMATION ---
        // distance_km * prix_km for confirmed/completed trips
        $revenue = $trajetRepo->createQueryBuilder('t')
            ->join('t.id_voiture', 'v')
            ->select('SUM(t.distance_km * v.prix_km)')
            ->where('t.statut IN (:status)')
            ->setParameter('status', ['Confirmé', 'Terminé'])
            ->getQuery()
            ->getSingleScalarResult();

        // --- BRAND DISTRIBUTION ---
        $brandCounts = $voitureRepo->createQueryBuilder('v')
            ->select('v.marque, COUNT(v.id_voiture) as count')
            ->groupBy('v.marque')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // --- PRICE STATS ---
        $priceData = $voitureRepo->createQueryBuilder('v')
            ->select('AVG(v.prix_km) as avg_price, MAX(v.prix_km) as max_price')
            ->getQuery()
            ->getOneOrNullResult();

        return new JsonResponse([
            'voitures' => [
                'total' => $totalVoitures,
                'disponibles' => $voituresDisponibles,
                'indisponibles' => $voituresIndisponibles,
                'taux_dispo' => $totalVoitures > 0 ? round(($voituresDisponibles / $totalVoitures) * 100, 1) : 0,
                'marques' => $brandCounts,
                'prix_moyen' => round((float)($priceData['avg_price'] ?? 0), 2),
                'prix_max' => round((float)($priceData['max_price'] ?? 0), 2)
            ],
            'trajets' => [
                'total' => $totalTrajets,
                'status' => $statusData,
                'total_distance' => round((float)($distData['total_dist'] ?? 0), 1),
                'avg_distance' => round((float)($distData['avg_dist'] ?? 0), 1)
            ],
            'revenue' => [
                'total_est' => round((float)($revenue ?? 0), 2)
            ]
        ]);
    }
}
