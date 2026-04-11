<?php

namespace App\Twig;

use App\Entity\Admin_notifications;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AdminNotifExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function getGlobals(): array
    {
        try {
            $repo = $this->em->getRepository(Admin_notifications::class);

            $unread = (int) $repo->createQueryBuilder('n')
                ->select('COUNT(n.id)')
                ->where('n.is_read = false')
                ->getQuery()
                ->getSingleScalarResult();

            $latest = $repo->createQueryBuilder('n')
                ->orderBy('n.created_at', 'DESC')
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