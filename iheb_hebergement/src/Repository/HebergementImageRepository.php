<?php

namespace App\Repository;

use App\Entity\HebergementImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HebergementImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HebergementImage::class);
    }

    public function findByHebergementOrdered(int $hebergementId): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.hebergement = :hebergementId')
            ->setParameter('hebergementId', $hebergementId)
            ->orderBy('i.ordre', 'ASC')
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
