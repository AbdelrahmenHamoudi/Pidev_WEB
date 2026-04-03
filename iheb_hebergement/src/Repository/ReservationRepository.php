<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.hebergement', 'h')
            ->addSelect('h')
            ->where('r.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByHebergement(int $hebergementId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.hebergement = :hebergementId')
            ->andWhere('r.statut IN (:statuts)')
            ->andWhere('r.dateFin >= :today')
            ->setParameter('hebergementId', $hebergementId)
            ->setParameter('statuts', ['Confirmée', 'EN ATTENTE', 'PENDING'])
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.hebergement', 'h')
            ->addSelect('h')
            ->where('r.dateDebut >= :today')
            ->andWhere('r.statut IN (:statuts)')
            ->setParameter('today', new \DateTime())
            ->setParameter('statuts', ['Confirmée', 'EN ATTENTE'])
            ->orderBy('r.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
