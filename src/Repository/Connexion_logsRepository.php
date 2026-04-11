<?php

namespace App\Repository;

use App\Entity\Connexion_logs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Connexion_logsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Connexion_logs::class);
    }

    // Add custom methods as needed
}