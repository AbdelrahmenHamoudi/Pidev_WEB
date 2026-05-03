<?php

namespace App\Twig;

use App\Entity\Notification;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AdminNotifExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function getGlobals(): array
    {
        try {
            $user = $this->security->getUser();

            if (!$user instanceof Users) {
                return [
                    'unreadAdminNotifCount' => 0,
                    'latestAdminNotifs'     => [],
                ];
            }

            $repo = $this->em->getRepository(Notification::class);

            $unread = (int) $repo->createQueryBuilder('n')
                ->select('COUNT(n.id)')
                ->where('n.user_id = :user')
                ->andWhere('n.lue = false')
                ->setParameter('user', $user)
                ->getQuery()
                ->getSingleScalarResult();

            $latest = $repo->createQueryBuilder('n')
                ->where('n.user_id = :user')
                ->setParameter('user', $user)
                ->orderBy('n.date_creation', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();

            return [
                'unreadAdminNotifCount' => $unread,
                'latestAdminNotifs'     => $latest,
            ];
        } catch (\Throwable) {
            return [
                'unreadAdminNotifCount' => 0,
                'latestAdminNotifs'     => [],
            ];
        }
    }
}