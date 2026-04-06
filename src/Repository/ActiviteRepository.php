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

    // Add custom methods as needed

    /**
     * @return Activite[] Returns an array of Activite objects
     */
    public function findDisponibles(): array
    {
        // Par défaut, s'il n'y a pas de colonne 'statut', on retourne toutes les activités
        return $this->createQueryBuilder('a')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Activite[] Returns an array of Activite objects
     */
    public function findByFilters(?string $type, ?float $prixMax): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($prixMax !== null) {
            $qb->andWhere('a.prixParPersonne <= :prixMax')
               ->setParameter('prixMax', $prixMax);
        }
        
        // Si vous ajoutez un champ 'type' plus tard sur l'entité Activite
        /*
        if ($type) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $type);
        }
        */

        return $qb->getQuery()->getResult();
    }
}