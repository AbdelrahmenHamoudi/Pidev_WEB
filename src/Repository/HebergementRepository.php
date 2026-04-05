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
            ->where('h.disponible_heberg = :disponible')
            ->setParameter('disponible', true)
            ->orderBy('h.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.typeHebergement = :type')
            ->andWhere('h.disponible_heberg = :disponible')
            ->setParameter('type', $type)
            ->setParameter('disponible', true)
            ->orderBy('h.prixParNuit', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $keyword): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.titre LIKE :keyword')
            ->orWhere('h.descHebergement LIKE :keyword')
            ->orWhere('h.typeHebergement LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('h.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
