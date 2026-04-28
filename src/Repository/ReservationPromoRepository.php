<?php

namespace App\Repository;

use App\Entity\ReservationPromo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationPromoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationPromo::class);
    }

    /**
     * Toutes les réservations promo d'un utilisateur, triées par date de création desc.
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie s'il existe un chevauchement de dates pour un hébergement donné.
     * (Empêche de réserver le même hébergement sur les mêmes dates)
     *
     * @param int                   $hebergementId
     * @param \DateTimeInterface    $start
     * @param \DateTimeInterface    $end
     * @param int|null              $excludeId  ID à exclure (pour les modifications)
     */
    public function findOverlappingHebergement(
        int $hebergementId,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $excludeId = null
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->where('r.hebergementId = :hId')
            ->andWhere('r.offerType = :type')
            ->andWhere('r.statut != :annulee')
            ->andWhere('r.dateDebut < :end')
            ->andWhere('r.dateFin > :start')
            ->setParameter('hId', $hebergementId)
            ->setParameter('type', 'hebergement')
            ->setParameter('annulee', ReservationPromo::STATUT_ANNULEE)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($excludeId) {
            $qb->andWhere('r.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche avancée dans les réservations promo (pour le backoffice / admin).
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('r')
            ->join('r.promotion', 'p')
            ->join('r.user', 'u');

        if (!empty($filters['offerType'])) {
            $qb->andWhere('r.offerType = :ot')
               ->setParameter('ot', $filters['offerType']);
        }

        if (!empty($filters['statut'])) {
            $qb->andWhere('r.statut = :statut')
               ->setParameter('statut', $filters['statut']);
        }

        $qb->orderBy('r.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }
}