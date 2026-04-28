<?php

namespace App\Repository;

use App\Entity\Voiture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voiture>
 */
class VoitureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voiture::class);
    }

    /**
     * Finds cars that are NOT reserved on a specific date.
     * Logic: A car is busy if it has a Trajet on the same YYYY-MM-DD
     * with a status other than 'Annulé'.
     */
    public function findAvailableCarsForDay(\DateTimeInterface $date, ?int $excludeTrajetId = null): array
    {
        $dateStr = $date->format('Y-m-d');
        
        $qb = $this->createQueryBuilder('v');
        
        $sub = $this->getEntityManager()->createQueryBuilder()
            ->select('t.id_trajet')
            ->from(\App\Entity\Trajet::class, 't')
            ->where('t.id_voiture = v')
            ->andWhere('t.date_reservation LIKE :datePart')
            ->andWhere('t.statut != :annule');

        if ($excludeTrajetId) {
            $sub->andWhere('t.id_trajet != :excludeId');
        }

        $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())))
           ->setParameter('datePart', $dateStr . '%')
           ->setParameter('annule', 'Annulé');

        if ($excludeTrajetId) {
            $qb->setParameter('excludeId', $excludeTrajetId);
        }

        return $qb->getQuery()->getResult();
    }
}
