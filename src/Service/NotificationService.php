<?php

namespace App\Service;

use App\Entity\Admin_notifications;
use App\Entity\Notification;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(private EntityManagerInterface $em) {}

    /** Notifier un utilisateur spécifique */
    public function notifyUser(Users $user, string $message): void
    {
        $notif = new Notification();
        $notif->setUser_id($user);
        $notif->setMessage($message);

        $this->em->persist($notif);
        $this->em->flush();
    }

    /** Créer une notification globale admin */
    public function notifyAdmin(
        string $title,
        string $message,
        string $type,
        string $priority,
        string $createdBy,
        ?string $actionLink = null,
        ?string $actionText = null,
    ): void {
        $notif = new Admin_notifications();
        $notif->setTitle($title);
        $notif->setMessage($message);
        $notif->setType($type);
        $notif->setPriority($priority);
        $notif->setCreated_by($createdBy);
        $notif->setAction_link($actionLink);
        $notif->setAction_text($actionText);

        $this->em->persist($notif);
        $this->em->flush();
    }

    public function getUnreadAdminCount(): int
    {
        return (int) $this->em->getRepository(Admin_notifications::class)
            ->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.is_read = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAdminRead(int $id): void
    {
        $notif = $this->em->getRepository(Admin_notifications::class)->find($id);
        if ($notif) {
            $notif->setIs_read(true);
            $this->em->flush();
        }
    }
}