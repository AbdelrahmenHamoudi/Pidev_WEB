<?php

namespace App\EventSubscriber;

use App\Entity\Notification;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class AdminNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private Environment $twig
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onController',
        ];
    }

    public function onController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route', '');
        if (!str_starts_with($route, 'app_admin')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof Users) {
            return;
        }

        $repo = $this->em->getRepository(Notification::class);

        $unreadCount = (int) $repo->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user_id = :user')
            ->andWhere('n.lue = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $latestNotifs = $repo->createQueryBuilder('n')
            ->where('n.user_id = :user')
            ->setParameter('user', $user)
            ->orderBy('n.date_creation', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $this->twig->addGlobal('unreadAdminNotifCount', $unreadCount);
        $this->twig->addGlobal('latestAdminNotifs', $latestNotifs);
    }
}