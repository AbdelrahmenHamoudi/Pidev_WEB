<?php

namespace App\Repository;

use App\Entity\Activite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activite::class);
    }

    // Toutes les activites disponibles
    public function findDisponibles(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.statut = :statut')
            ->setParameter('statut', 'Disponible')
            ->orderBy('a.nomA', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Recherche par type
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.type = :type')
            ->setParameter('type', $type)
            ->orderBy('a.nomA', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Recherche par lieu (partielle, insensible casse)
    public function findByLieu(string $lieu): array
    {
        return $this->createQueryBuilder('a')
            ->where('LOWER(a.lieu) LIKE LOWER(:lieu)')
            ->setParameter('lieu', '%' . $lieu . '%')
            ->getQuery()
            ->getResult();
    }

    // Activite avec ses plannings disponibles
    public function findWithPlanningsDisponibles(int $id): ?Activite
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.plannings', 'p')
            ->addSelect('p')
            ->where('a.idActivite = :id')
            ->andWhere('p.etat = :etat')
            ->andWhere('p.nbPlacesRestantes > 0')
            ->setParameter('id', $id)
            ->setParameter('etat', 'Disponible')
            ->orderBy('p.datePlanning', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Filtre combiné type + prix max
    public function findByFilters(?string $type, ?float $prixMax): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($type) {
            $qb->andWhere('a.type = :type')->setParameter('type', $type);
        }
        if ($prixMax) {
            $qb->andWhere('a.prixParPersonne <= :prix')->setParameter('prix', $prixMax);
        }

        return $qb->orderBy('a.prixParPersonne', 'ASC')->getQuery()->getResult();
    }
}
