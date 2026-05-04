<?php

namespace App\Tests\Service;

use App\Entity\Hebergement;
use App\Service\Hebergement\HebergementManager;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class HebergementManagerTest extends TestCase
{
    public function testValidHebergement(): void
    {
        $hebergement = new Hebergement();
        $hebergement->setTitre('Magnifique Villa');
        $hebergement->setDesc_hebergement('Une superbe villa au bord de la mer avec piscine.');
        $hebergement->setPrixParNuit('150.0');
        $hebergement->setCapacite(4);

        $manager = new HebergementManager();
        $this->assertTrue($manager->validate($hebergement));
    }

    public function testHebergementWithoutTitle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire');

        $hebergement = new Hebergement();
        $hebergement->setDesc_hebergement('Une superbe villa au bord de la mer.');
        $hebergement->setPrixParNuit('150.0');
        $hebergement->setCapacite(4);

        $manager = new HebergementManager();
        $manager->validate($hebergement);
    }

    public function testHebergementWithShortDescription(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir au moins 10 caractères');

        $hebergement = new Hebergement();
        $hebergement->setTitre('Magnifique Villa');
        $hebergement->setDesc_hebergement('Court');
        $hebergement->setPrixParNuit('150.0');
        $hebergement->setCapacite(4);

        $manager = new HebergementManager();
        $manager->validate($hebergement);
    }

    public function testHebergementWithInvalidPrice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix doit être supérieur à zéro');

        $hebergement = new Hebergement();
        $hebergement->setTitre('Magnifique Villa');
        $hebergement->setDesc_hebergement('Une superbe villa au bord de la mer.');
        $hebergement->setPrixParNuit('0');
        $hebergement->setCapacite(4);

        $manager = new HebergementManager();
        $manager->validate($hebergement);
    }

    public function testHebergementWithInvalidCapacity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité doit être supérieure à zéro');

        $hebergement = new Hebergement();
        $hebergement->setTitre('Magnifique Villa');
        $hebergement->setDesc_hebergement('Une superbe villa au bord de la mer.');
        $hebergement->setPrixParNuit('150.0');
        $hebergement->setCapacite(0);

        $manager = new HebergementManager();
        $manager->validate($hebergement);
    }
}
