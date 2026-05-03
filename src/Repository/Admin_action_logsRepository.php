<?php

namespace App\Repository;

use App\Entity\Admin_action_logs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Admin_action_logsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Admin_action_logs::class);
    }

    // Add custom methods as needed
}