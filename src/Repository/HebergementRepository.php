<?php

namespace App\Repository;

use App\Entity\Hebergement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HebergementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hebergement::class);
    }

    public function findAvailable(): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.disponible_heberg = :val')
            ->setParameter('val', true)
            ->getQuery()
            ->getResult();
    }
}