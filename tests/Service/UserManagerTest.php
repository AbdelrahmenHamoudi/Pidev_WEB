<?php

namespace App\Tests\Service;

use App\Entity\Users;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private UserManager $manager;

    protected function setUp(): void
    {
        $this->manager = new UserManager();
    }

    /**
     * Crée un utilisateur valide de base pour les tests.
     */
    private function createValidUser(): Users
    {
        $user = new Users();
        $user->setNom('Ben Ali');
        $user->setPrenom('Mohamed');
        $user->setE_mail('mohamed.benali@gmail.com');
        $user->setNum_tel('22334455');
        $user->setMot_de_pass('motdepasse123');
        $user->setRole('user');
        $user->setStatus('Unbanned');
        return $user;
    }

    // ✅ TEST 1 : Un utilisateur valide doit passer la validation
    public function testValidUser(): void
    {
        $user = $this->createValidUser();
        $this->assertTrue($this->manager->validate($user));
    }

    // ❌ TEST 2 : Le nom vide doit lever une exception
    public function testUserWithEmptyNom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire.');

        $user = $this->createValidUser();
        $user->setNom('   ');
        $this->manager->validate($user);
    }

    // ❌ TEST 3 : Le prénom vide doit lever une exception
    public function testUserWithEmptyPrenom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom est obligatoire.');

        $user = $this->createValidUser();
        $user->setPrenom('');
        $this->manager->validate($user);
    }

    // ❌ TEST 4 : Un email invalide doit lever une exception
    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'adresse email est invalide.');

        $user = $this->createValidUser();
        $user->setE_mail('email_invalide');
        $this->manager->validate($user);
    }

    // ❌ TEST 5 : Email sans domaine doit lever une exception
    public function testUserWithEmailWithoutDomain(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = $this->createValidUser();
        $user->setE_mail('test@');
        $this->manager->validate($user);
    }

    // ❌ TEST 6 : Numéro de téléphone trop court doit lever une exception
    public function testUserWithShortPhoneNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le numéro de téléphone doit contenir exactement 8 chiffres.');

        $user = $this->createValidUser();
        $user->setNum_tel('1234');
        $this->manager->validate($user);
    }

    // ❌ TEST 7 : Numéro de téléphone avec lettres doit lever une exception
    public function testUserWithLettersInPhoneNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le numéro de téléphone doit contenir exactement 8 chiffres.');

        $user = $this->createValidUser();
        $user->setNum_tel('1234abcd');
        $this->manager->validate($user);
    }

    // ❌ TEST 8 : Mot de passe trop court doit lever une exception
    public function testUserWithShortPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 8 caractères.');

        $user = $this->createValidUser();
        $user->setMot_de_pass('123');
        $this->manager->validate($user);
    }

    // ✅ TEST 9 : Un admin valide doit passer la validation
    public function testValidAdminUser(): void
    {
        $user = $this->createValidUser();
        $user->setRole('admin');
        $this->assertTrue($this->manager->validate($user));
    }

    // ✅ TEST 10 : Un utilisateur banni valide doit passer la validation
    public function testValidBannedUser(): void
    {
        $user = $this->createValidUser();
        $user->setStatus('Banned');
        $this->assertTrue($this->manager->validate($user));
    }
}