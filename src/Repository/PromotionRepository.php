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
}