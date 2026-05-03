<?php

namespace App\Repository;

use App\Entity\Admin_notifications;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Admin_notificationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Admin_notifications::class);
    }

    // Add custom methods as needed
}