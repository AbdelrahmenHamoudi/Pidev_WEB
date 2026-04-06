<?php

namespace App\Security;

use App\Entity\Users;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Users) {
            return;
        }

        if ($user->getStatus() === 'Banned') {
            throw new CustomUserMessageAccountStatusException('Votre compte a été banni. Veuillez contacter l’administrateur.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof Users) {
            return;
        }

        if ($user->getStatus() === 'Banned') {
            throw new CustomUserMessageAccountStatusException('Votre compte a été banni. Veuillez contacter l’administrateur.');
        }
    }
}