<?php

namespace App\Service;

use App\Entity\Promotion;
use App\Repository\ReservationPromoRepository;

class TrendingService
{
    public const SEUIL_VUES         = 10;
    public const SEUIL_RESERVATIONS = 5;
    public const POIDS_VUES         = 0.4;
    public const POIDS_RESA         = 0.6;

    public function __construct(
        private ReservationPromoRepository $resaRepo,
    ) {}

    public function getNbReservations(Promotion $p): int
    {
        return (int) $this->resaRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.promotion = :p')
            ->andWhere('r.statut != :annulee')
            ->setParameter('p', $p)
            ->setParameter('annulee', 'ANNULEE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getNbVues(Promotion $p): int
    {
        return $p->getViews();
    }

    public function isTrending(Promotion $p): bool
    {
        return $this->getNbVues($p) > self::SEUIL_VUES
            && $this->getNbReservations($p) > self::SEUIL_RESERVATIONS;
    }

    public function getTrendingScore(Promotion $p): float
    {
        return ($this->getNbVues($p) * self::POIDS_VUES)
             + ($this->getNbReservations($p) * self::POIDS_RESA);
    }

    /** @param Promotion[] $allPromos */
    public function getNormalizedScore(Promotion $p, array $allPromos): float
    {
        $max = 0.0;
        foreach ($allPromos as $promo) {
            $s = $this->getTrendingScore($promo);
            if ($s > $max) $max = $s;
        }
        if ($max === 0.0) return 0.0;
        return min(1.0, $this->getTrendingScore($p) / $max);
    }

    /**
     * @param Promotion[] $promos
     * @return Promotion[]
     */
    public function sortWithTrendingFirst(array $promos): array
    {
        usort($promos, function (Promotion $a, Promotion $b) {
            $ta = $this->isTrending($a) ? 0 : 1;
            $tb = $this->isTrending($b) ? 0 : 1;
            if ($ta !== $tb) return $ta <=> $tb;
            return $this->getTrendingScore($b) <=> $this->getTrendingScore($a);
        });
        return $promos;
    }

    /** @param Promotion[] $promos */
    public function countTrending(array $promos): int
    {
        return count(array_filter($promos, fn(Promotion $p) => $this->isTrending($p)));
    }

    public function getScoreLabel(Promotion $p): string
    {
        $score = $this->getTrendingScore($p);
        if ($score >= 50) return '🔥🔥 Viral';
        if ($score >= 25) return '🔥 Tendance';
        if ($score >= 10) return '📈 Montant';
        if ($score >= 3)  return '👀 Remarqué';
        return '💤 Dormant';
    }

    public function getScoreColor(Promotion $p): string
    {
        $score = $this->getTrendingScore($p);
        if ($score >= 50) return '#E53E3E';
        if ($score >= 25) return '#F39C12';
        if ($score >= 10) return '#1ABC9C';
        if ($score >= 3)  return '#3498DB';
        return '#94A3B8';
    }
}