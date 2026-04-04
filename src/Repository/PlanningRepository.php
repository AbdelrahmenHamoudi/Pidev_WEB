<?php

namespace App\Repository;

use App\Entity\Planning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlanningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planning::class);
    }

    // Plannings disponibles pour une activite
    public function findDisponiblesByActivite(int $idActivite): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.activite = :id')
            ->andWhere('p.etat = :etat')
            ->andWhere('p.nbPlacesRestantes > 0')
            ->andWhere('p.datePlanning >= :today')
            ->setParameter('id', $idActivite)
            ->setParameter('etat', 'Disponible')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('p.datePlanning', 'ASC')
            ->addOrderBy('p.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Plannings d'une activite filtres par date
    public function findByActiviteAndDate(int $idActivite, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.activite = :id')
            ->andWhere('p.datePlanning = :date')
            ->setParameter('id', $idActivite)
            ->setParameter('date', $date)
            ->orderBy('p.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Tous les plannings futurs
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.activite', 'a')
            ->addSelect('a')
            ->where('p.datePlanning >= :today')
            ->andWhere('p.etat = :etat')
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('etat', 'Disponible')
            ->orderBy('p.datePlanning', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
