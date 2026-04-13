<?php

namespace App\EventListener;

use App\Entity\Users;
use App\Service\ConnexionLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener implements EventSubscriberInterface
{
    private ConnexionLogger $connexionLogger;

    public function __construct(ConnexionLogger $connexionLogger)
    {
        $this->connexionLogger = $connexionLogger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();

        if (!$token) {
            return;
        }

        $user = $token->getUser();

        if ($user instanceof Users) {
            $this->connexionLogger->logLogout($user);
        }
    }
}