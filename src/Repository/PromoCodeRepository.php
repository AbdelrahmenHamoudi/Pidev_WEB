<?php

namespace App\Repository;

use App\Entity\PromoCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PromoCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromoCode::class);
    }

    public function findByPromotion(int $promotionId): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.promotionId = :pid')
            ->setParameter('pid', $promotionId)
            ->orderBy('pc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveCode(int $promotionId): ?PromoCode
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.promotionId = :pid')
            ->andWhere('pc.isUsed = false')
            ->setParameter('pid', $promotionId)
            ->orderBy('pc.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}