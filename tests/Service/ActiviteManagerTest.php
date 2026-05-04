<?php
namespace App\Tests\Service;

use App\Entity\Activite;
use App\Entity\Planningactivite;
use App\Service\ActiviteManager;
use PHPUnit\Framework\TestCase;

class ActiviteManagerTest extends TestCase
{
    private ActiviteManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ActiviteManager();
    }

    // ══════════════════════════════════════════════════
    //  TESTS ACTIVITE
    // ══════════════════════════════════════════════════

    // ✅ Test 1 : Activité valide
    public function testActiviteValide(): void
    {
        $activite = new Activite();
        $activite->setNomA('Randonnée Zaghouan');
        $activite->setPrixParPersonne(50.0);
        $activite->setCapaciteMax(20);

        $this->assertTrue($this->manager->validateActivite($activite));
    }

    // ❌ Test 2 : Nom vide
    public function testActiviteSansNom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de l\'activité est obligatoire');

        $activite = new Activite();
        $activite->setNomA('');
        $activite->setPrixParPersonne(50.0);
        $activite->setCapaciteMax(20);

        $this->manager->validateActivite($activite);
    }

    // ❌ Test 3 : Prix négatif
    public function testActivitePrixNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix doit être supérieur à zéro');

        $activite = new Activite();
        $activite->setNomA('Randonnée');
        $activite->setPrixParPersonne(-10.0);
        $activite->setCapaciteMax(20);

        $this->manager->validateActivite($activite);
    }

    // ❌ Test 4 : Capacité = 0
    public function testActiviteCapaciteZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité doit être au moins 1');

        $activite = new Activite();
        $activite->setNomA('Randonnée');
        $activite->setPrixParPersonne(50.0);
        $activite->setCapaciteMax(0);

        $this->manager->validateActivite($activite);
    }

    // ══════════════════════════════════════════════════
    //  TESTS PLANNING
    // ══════════════════════════════════════════════════

    // ✅ Test 5 : Planning valide
    public function testPlanningValide(): void
    {
        $planning = new Planningactivite();
        $planning->setHeureDebut('09:00');
        $planning->setHeureFin('12:00');
        $planning->setNbPlacesRestantes(10);

        $this->assertTrue($this->manager->validatePlanning($planning));
    }

    // ❌ Test 6 : Heure fin avant heure debut
    public function testPlanningHeureFinAvantDebut(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'heure de fin doit être après l\'heure de début');

        $planning = new Planningactivite();
        $planning->setHeureDebut('14:00');
        $planning->setHeureFin('09:00');
        $planning->setNbPlacesRestantes(10);

        $this->manager->validatePlanning($planning);
    }

    // ❌ Test 7 : Places négatives
    public function testPlanningPlacesNegatives(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de places ne peut pas être négatif');

        $planning = new Planningactivite();
        $planning->setHeureDebut('09:00');
        $planning->setHeureFin('12:00');
        $planning->setNbPlacesRestantes(-5);

        $this->manager->validatePlanning($planning);
    }

    // ══════════════════════════════════════════════════
    //  TESTS METIER — reserverPlace()
    // ══════════════════════════════════════════════════

    // ✅ Test 8 : Réservation décrémente les places
    public function testReserverPlaceDecremente(): void
    {
        $planning = new Planningactivite();
        $planning->setNbPlacesRestantes(5);
        $planning->setEtat('Disponible');

        $planning->reserverPlace();

        $this->assertEquals(4, $planning->getNbPlacesRestantes());
        $this->assertEquals('Disponible', $planning->getEtat());
    }

    // ✅ Test 9 : Dernière place → état devient Complet
    public function testReserverDernierePlace(): void
    {
        $planning = new Planningactivite();
        $planning->setNbPlacesRestantes(1);
        $planning->setEtat('Disponible');

        $planning->reserverPlace();

        $this->assertEquals(0, $planning->getNbPlacesRestantes());
        $this->assertEquals('Complet', $planning->getEtat());
    }

    // ✅ Test 10 : isDisponible() → false si Complet
    public function testIsDisponibleFalsiSiComplet(): void
    {
        $planning = new Planningactivite();
        $planning->setNbPlacesRestantes(0);
        $planning->setEtat('Complet');

        $this->assertFalse($planning->isDisponible());
    }

    // ✅ Test 11 : isDisponible() → true si places > 0
    public function testIsDisponibleTrueSiPlacesDisponibles(): void
    {
        $planning = new Planningactivite();
        $planning->setNbPlacesRestantes(5);
        $planning->setEtat('Disponible');

        $this->assertTrue($planning->isDisponible());
    }
}