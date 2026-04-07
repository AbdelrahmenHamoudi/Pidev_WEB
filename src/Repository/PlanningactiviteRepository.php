<?php

namespace App\Repository;

use App\Entity\Planningactivite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlanningactiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planningactivite::class);
    }

    /**
     * Retourne les plannings disponibles pour une activite donnee
     */
    public function findDisponiblesByActivite(int $idActivite): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.activite = :idActivite')
            ->andWhere('p.etat = :etat')
            ->andWhere('p.nbPlacesRestantes > 0')
            ->setParameter('idActivite', $idActivite)
            ->setParameter('etat', 'Disponible')
            ->orderBy('p.datePlanning', 'ASC')
            ->getQuery()
            ->getResult();
    }
}