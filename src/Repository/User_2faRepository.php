<?php

namespace App\Repository;

use App\Entity\User_2fa;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class User_2faRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User_2fa::class);
    }

    // Add custom methods as needed
}