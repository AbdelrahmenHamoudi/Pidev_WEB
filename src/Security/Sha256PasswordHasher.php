<?php

namespace App\Security;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class Sha256PasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return hash('sha256', $plainPassword);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return hash_equals($hashedPassword, hash('sha256', $plainPassword));
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}