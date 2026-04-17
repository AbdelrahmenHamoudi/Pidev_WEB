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

    /**
     * Checks if there are any overlapping reservations (confirmed or pending)
     * for a given hebergement and date range.
     */
    public function findOverlappingReservations(int $hebergementId, \DateTimeInterface $start, \DateTimeInterface $end, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.hebergement_id = :hId')
            ->andWhere('r.statutR IN (:statuses)')
            ->andWhere('r.dateDebutR < :end')
            ->andWhere('r.dateFinR > :start')
            ->setParameter('hId', $hebergementId)
            ->setParameter('statuses', ['CONFIRMEE', 'EN ATTENTE', 'PENDING'])
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($excludeId) {
            $qb->andWhere('r.id_reservation != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }
}