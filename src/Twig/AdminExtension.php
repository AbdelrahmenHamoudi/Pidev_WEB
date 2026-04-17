<?php

namespace App\Twig;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminExtension extends AbstractExtension
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('users_count', [$this, 'getUsersCount']),
        ];
    }

    public function getUsersCount(): int
    {
        return (int) $this->entityManager
            ->getRepository(Users::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}