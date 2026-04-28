<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function notifyUser(Users $user, string $message): void
    {
        $notif = new Notification();
        $notif->setUser_id($user);
        $notif->setMessage($message);

        $this->em->persist($notif);
        $this->em->flush();
    }

    public function notifyAllAdmins(string $message): void
    {
        $admins = $this->em->getRepository(Users::class)->findBy(['role' => 'admin']);

        foreach ($admins as $admin) {
            $notif = new Notification();
            $notif->setUser_id($admin);
            $notif->setMessage($message);
            $this->em->persist($notif);
        }

        $this->em->flush();
    }

    public function getUnreadCount(Users $user): int
    {
        return (int) $this->em->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user_id = :user')
            ->andWhere('n.lue = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAsRead(int $id): void
    {
        $notif = $this->em->getRepository(Notification::class)->find($id);
        if ($notif) {
            $notif->setLue(true);
            $this->em->flush();
        }
    }

    public function markAllRead(Users $user): void
    {
        $this->em->createQuery(
            'UPDATE App\Entity\Notification n SET n.lue = true WHERE n.user_id = :user AND n.lue = false'
        )->setParameter('user', $user)->execute();
    }
}