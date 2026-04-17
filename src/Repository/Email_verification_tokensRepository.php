<?php

namespace App\Repository;

use App\Entity\Email_verification_tokens;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Email_verification_tokensRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Email_verification_tokens::class);
    }

    // Add custom methods as needed
}