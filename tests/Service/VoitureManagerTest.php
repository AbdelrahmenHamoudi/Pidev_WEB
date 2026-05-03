<?php

namespace App\Tests\Service;

use App\Entity\Voiture;
use App\Service\VoitureManager;
use PHPUnit\Framework\TestCase;

class VoitureManagerTest extends TestCase
{
    private VoitureManager $manager;

    protected function setUp(): void
    {
        $this->manager = new VoitureManager();
    }

    // ✅ Test 1 : Voiture valide
    public function testVoitureValide(): void
    {
        $voiture = new Voiture();
        $voiture->setMarque('Toyota');
        $voiture->setModele('Corolla');
        $voiture->setImmatriculation('TN-123-AB');
        $voiture->setPrixKM(1.5);
        $voiture->setNbPlaces(5);

        $this->assertTrue($this->manager->validate($voiture));
    }

    // ❌ Test 2 : Marque manquante
    public function testVoitureSansMarque(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La marque est obligatoire.');

        $voiture = new Voiture();
        $voiture->setImmatriculation('TN-123-AB');
        $voiture->setPrixKM(1.5);
        $voiture->setNbPlaces(5);

        $this->manager->validate($voiture);
    }

    // ❌ Test 3 : Immatriculation manquante
    public function testVoitureSansImmatriculation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'immatriculation est obligatoire.");

        $voiture = new Voiture();
        $voiture->setMarque('Toyota');
        $voiture->setPrixKM(1.5);
        $voiture->setNbPlaces(5);

        $this->manager->validate($voiture);
    }

    // ❌ Test 4 : Prix KM invalide (zéro)
    public function testVoiturePrixKMNul(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix par km doit être supérieur à 0.');

        $voiture = new Voiture();
        $voiture->setMarque('Toyota');
        $voiture->setImmatriculation('TN-123-AB');
        $voiture->setPrixKM(0);
        $voiture->setNbPlaces(5);

        $this->manager->validate($voiture);
    }

    // ❌ Test 5 : Prix KM négatif
    public function testVoiturePrixKMNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix par km doit être supérieur à 0.');

        $voiture = new Voiture();
        $voiture->setMarque('Toyota');
        $voiture->setImmatriculation('TN-123-AB');
        $voiture->setPrixKM(-2.5);
        $voiture->setNbPlaces(5);

        $this->manager->validate($voiture);
    }

    // ❌ Test 6 : Nombre de places invalide
    public function testVoiturePlacesInvalides(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de places doit être entre 1 et 9.');

        $voiture = new Voiture();
        $voiture->setMarque('Toyota');
        $voiture->setImmatriculation('TN-123-AB');
        $voiture->setPrixKM(1.5);
        $voiture->setNbPlaces(0); // invalide

        $this->manager->validate($voiture);
    }
}
