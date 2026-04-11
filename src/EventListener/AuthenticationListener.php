<?php

namespace App\EventListener;

use App\Entity\Users;
use App\Service\ConnexionLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
#[AsEventListener(event: LoginFailureEvent::class)]
#[AsEventListener(event: LogoutEvent::class)]
class AuthenticationListener
{
    public function __construct(private ConnexionLogger $logger) {}

    public function __invoke(LoginSuccessEvent|LoginFailureEvent|LogoutEvent $event): void
    {
        match (true) {
            $event instanceof LoginSuccessEvent => $this->onSuccess($event),
            $event instanceof LoginFailureEvent => $this->onFailure($event),
            $event instanceof LogoutEvent       => $this->onLogout($event),
        };
    }

    private function onSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getAuthenticatedToken()->getUser();
        if ($user instanceof Users) {
            $this->logger->logSuccess($user);
        }
    }

    private function onFailure(LoginFailureEvent $event): void
    {
        $reason = $event->getException()->getMessage();
        $this->logger->logFailure(null, $reason);
    }

    private function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if ($user instanceof Users) {
            $this->logger->logLogout($user);
        }
    }
}