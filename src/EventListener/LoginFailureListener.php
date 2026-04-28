<?php

namespace App\EventListener;

use App\Entity\Users;
use App\Service\ConnexionLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class LoginFailureListener implements EventSubscriberInterface
{
    private ConnexionLogger $connexionLogger;
    private EntityManagerInterface $em;

    public function __construct(ConnexionLogger $connexionLogger, EntityManagerInterface $em)
    {
        $this->connexionLogger = $connexionLogger;
        $this->em = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getException();

        // Recuperer l'email saisi
        $email = $request->request->get('_username', '');

        // Essayer de trouver l'utilisateur par email
        $user = null;
        if (!empty($email)) {
            $user = $this->em->getRepository(Users::class)->findOneBy([
                'e_mail' => strtolower(trim($email)),
            ]);
        }

        // Determiner la raison de l'echec
        $failureReason = 'Identifiants invalides';
        $exceptionMessage = $exception->getMessage();

        if (str_contains($exceptionMessage, 'banni') || str_contains($exceptionMessage, 'Banned')) {
            $failureReason = 'Compte banni';
        } elseif (str_contains($exceptionMessage, 'credentials') || str_contains($exceptionMessage, 'Invalid')) {
            $failureReason = 'Mot de passe incorrect';
        } elseif (str_contains($exceptionMessage, 'not found') || str_contains($exceptionMessage, 'identifier')) {
            $failureReason = 'Email non trouve';
        } elseif (str_contains($exceptionMessage, 'robot') || str_contains($exceptionMessage, 'reCAPTCHA')) {
            $failureReason = 'Echec reCAPTCHA';
        }

        // Logger l'echec avec geolocalisation
        $this->connexionLogger->logFailure($user, $failureReason);
    }
}