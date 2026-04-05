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
            ->where('r.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.date_debut_r', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByHebergement(int $hebergementId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.hebergement = :hebergementId')
            ->andWhere('r.statut_r IN (:statuts)')
            ->andWhere('r.date_fin_r >= :today')
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
            ->where('r.date_debut_r >= :today')
            ->andWhere('r.statut_r IN (:statuts)')
            ->setParameter('today', new \DateTime())
            ->setParameter('statuts', ['Confirmée', 'EN ATTENTE'])
            ->orderBy('r.date_debut_r', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds reservations that overlap with the given date range for a specific property.
     */
    public function findOverlappingReservations(int $hebergementId, \DateTimeInterface $start, \DateTimeInterface $end, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.hebergement = :hebergementId')
            ->andWhere('r.statut_r NOT IN (:excludedStatuts)')
            ->andWhere('r.date_debut_r < :end')
            ->andWhere('r.date_fin_r > :start')
            ->setParameter('hebergementId', $hebergementId)
            ->setParameter('end', $end)
            ->setParameter('start', $start)
            ->setParameter('excludedStatuts', ['Annulée', 'ANNULEE', 'Refusée', 'REFUSEE']);

        if ($excludeId) {
            $qb->andWhere('r.id_reservation != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }
}
