<?php

namespace App\Tests\Service;

use App\Entity\Trajet;
use App\Service\TrajetManager;
use PHPUnit\Framework\TestCase;

class TrajetManagerTest extends TestCase
{
    private TrajetManager $manager;

    protected function setUp(): void
    {
        $this->manager = new TrajetManager();
    }

    // ✅ Test 1 : Trajet valide
    public function testTrajetValide(): void
    {
        $trajet = new Trajet();
        $trajet->setPointDepart('Tunis');
        $trajet->setPointArrivee('Sfax');
        $trajet->setDistanceKm(270.0);
        $trajet->setNbPersonnes(2);

        $this->assertTrue($this->manager->validate($trajet));
    }

    // ❌ Test 2 : Point de départ manquant
    public function testTrajetSansDepart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le point de départ est obligatoire.');

        $trajet = new Trajet();
        $trajet->setPointArrivee('Sfax');
        $trajet->setDistanceKm(270.0);
        $trajet->setNbPersonnes(2);

        $this->manager->validate($trajet);
    }

    // ❌ Test 3 : Point d'arrivée manquant
    public function testTrajetSansArrivee(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le point d'arrivée est obligatoire.");

        $trajet = new Trajet();
        $trajet->setPointDepart('Tunis');
        $trajet->setDistanceKm(270.0);
        $trajet->setNbPersonnes(2);

        $this->manager->validate($trajet);
    }

    // ❌ Test 4 : Distance nulle
    public function testTrajetDistanceNulle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La distance doit être supérieure à 0.');

        $trajet = new Trajet();
        $trajet->setPointDepart('Tunis');
        $trajet->setPointArrivee('Sfax');
        $trajet->setDistanceKm(0);
        $trajet->setNbPersonnes(2);

        $this->manager->validate($trajet);
    }

    // ❌ Test 5 : Distance négative
    public function testTrajetDistanceNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La distance doit être supérieure à 0.');

        $trajet = new Trajet();
        $trajet->setPointDepart('Tunis');
        $trajet->setPointArrivee('Sfax');
        $trajet->setDistanceKm(-50.0);
        $trajet->setNbPersonnes(2);

        $this->manager->validate($trajet);
    }

    // ❌ Test 6 : Zéro personne
    public function testTrajetZeroPersonne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de personnes doit être au moins 1.');

        $trajet = new Trajet();
        $trajet->setPointDepart('Tunis');
        $trajet->setPointArrivee('Sfax');
        $trajet->setDistanceKm(270.0);
        $trajet->setNbPersonnes(0);

        $this->manager->validate($trajet);
    }
}
