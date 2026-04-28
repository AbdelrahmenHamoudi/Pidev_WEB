<?php

namespace App\Repository;

use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    /** Promotions actives aujourd'hui */
    public function findActive(): array
    {
        $today = new \DateTime('today');
        return $this->createQueryBuilder('p')
            ->andWhere('p.startDate <= :today')
            ->andWhere('p.endDate >= :today')
            ->setParameter('today', $today)
            ->orderBy('p.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Toutes les promos de type pack */
    public function findPacks(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere("p.promoType = 'pack'")
            ->getQuery()
            ->getResult();
    }

    /** Toutes les promos individuelles */
    public function findIndividuelles(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere("p.promoType = 'individuelle'")
            ->getQuery()
            ->getResult();
    }

    public function search(?string $query, ?string $type, ?string $offerType, ?string $status, bool $tendance = false): array
    {
        $qb = $this->createQueryBuilder('p');
        $today = new \DateTime('today');

        if ($query) {
            $qb->andWhere('p.name LIKE :query OR p.description LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($type) {
            $qb->andWhere('p.promoType = :type')
               ->setParameter('type', $type);
        }

        if ($offerType) {
            $qb->andWhere('p.offerType = :offerType')
               ->setParameter('offerType', $offerType);
        }

        if ($status) {
            if ($status === 'active') {
                $qb->andWhere('p.startDate <= :today AND p.endDate >= :today');
            } elseif ($status === 'expired') {
                $qb->andWhere('p.endDate < :today');
            } elseif ($status === 'upcoming') {
                $qb->andWhere('p.startDate > :today');
            }
            $qb->setParameter('today', $today);
        }

        if ($tendance) {
            // Trending definition: views > 10 AND reservations > 5
            $qb->andWhere('p.views > :seuilVues')
               ->setParameter('seuilVues', 10);
            
            // Subquery to count non-cancelled reservations
            $qb->andWhere('(SELECT COUNT(r.id) FROM App\Entity\ReservationPromo r WHERE r.promotion = p AND r.statut != :annulee) > :seuilResa')
               ->setParameter('seuilResa', 5)
               ->setParameter('annulee', 'ANNULEE');
        }

        return $qb->orderBy('p.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}