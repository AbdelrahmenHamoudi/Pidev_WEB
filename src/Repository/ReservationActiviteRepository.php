<?php

namespace App\Repository;

use App\Entity\ReservationActivite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationActivite::class);
    }

    // Toutes les réservations d'un planning
    public function findByPlanning(int $idPlanning): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.planning = :id')
            ->setParameter('id', $idPlanning)
            ->orderBy('r.dateReservation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Toutes les réservations d'un utilisateur
    public function findByUtilisateur(int $idUtilisateur): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.planning', 'p')
            ->join('p.activite', 'a')
            ->addSelect('p', 'a')
            ->where('r.utilisateur = :id')
            ->setParameter('id', $idUtilisateur)
            ->orderBy('r.dateReservation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Toutes les réservations d'une activité (via planning)
    public function findByActivite(int $idActivite): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.planning', 'p')
            ->addSelect('p')
            ->where('p.activite = :id')
            ->setParameter('id', $idActivite)
            ->orderBy('r.dateReservation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Vérifier si un utilisateur a déjà réservé ce planning
    public function existeDejaReservation(int $idPlanning, int $idUtilisateur): bool
    {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.planning = :planning')
            ->andWhere('r.utilisateur = :user')
            ->andWhere('r.statut != :annulee')
            ->setParameter('planning', $idPlanning)
            ->setParameter('user', $idUtilisateur)
            ->setParameter('annulee', 'Annulee')
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
