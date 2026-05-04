<?php

namespace App\Tests\Service;

use App\Entity\Publication;
use App\Service\PublicationManager;
use PHPUnit\Framework\TestCase;

class PublicationManagerTest extends TestCase
{
    private PublicationManager $manager;

    protected function setUp(): void
    {
        $this->manager = new PublicationManager();
    }

    /**
     * Test that a valid publication passes validation
     */
    public function testValidPublication(): void
    {
        $publication = new Publication();
        $publication->setDescriptionP('Ceci est une publication valide pour le test');
        $publication->setTypeCible('HEBERGEMENT');

        $result = $this->manager->validate($publication);

        $this->assertTrue($result);
    }

    /**
     * Test that publication without name (empty description) throws exception
     * Rule: "Le nom est obligatoire" -> "La description doit contenir au moins 5 caractères"
     */
    public function testPublicationWithoutDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir au moins 5 caractères');

        $publication = new Publication();
        $publication->setDescriptionP('');
        $publication->setTypeCible('HEBERGEMENT');

        $this->manager->validate($publication);
    }

    /**
     * Test that publication with short description throws exception
     * Rule: "La description doit contenir au moins 5 caractères"
     */
    public function testPublicationWithShortDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir au moins 5 caractères');

        $publication = new Publication();
        $publication->setDescriptionP('AB');
        $publication->setTypeCible('HEBERGEMENT');

        $this->manager->validate($publication);
    }

    /**
     * Test that publication with forbidden word "bhim" throws exception
     * Rule: "Le contenu ne doit pas contenir de mots interdits"
     */
    public function testPublicationWithForbiddenWordBhim(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description contient des mots interdits : bhim');

        $publication = new Publication();
        $publication->setDescriptionP('Ce texte contient le mot bhim interdit');
        $publication->setTypeCible('HEBERGEMENT');

        $this->manager->validate($publication);
    }

    /**
     * Test that publication with forbidden word "kalb" throws exception
     */
    public function testPublicationWithForbiddenWordKalb(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description contient des mots interdits : kalb');

        $publication = new Publication();
        $publication->setDescriptionP('Ce texte contient kalb comme mot interdit');
        $publication->setTypeCible('HEBERGEMENT');

        $this->manager->validate($publication);
    }

    /**
     * Test that publication with forbidden word "couchon" throws exception
     */
    public function testPublicationWithForbiddenWordCouchon(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description contient des mots interdits : couchon');

        $publication = new Publication();
        $publication->setDescriptionP('Attention au mot couchon ici');
        $publication->setTypeCible('HEBERGEMENT');

        $this->manager->validate($publication);
    }

    /**
     * Test that publication with multiple forbidden words throws exception
     */
    public function testPublicationWithMultipleForbiddenWords(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $publication = new Publication();
        $publication->setDescriptionP('Texte avec bhim et kalb');
        $publication->setTypeCible('HEBERGEMENT');

        $this->manager->validate($publication);
    }

    /**
     * Test that publication description at minimum length (5 chars) passes
     */
    public function testPublicationWithMinimumLength(): void
    {
        $publication = new Publication();
        $publication->setDescriptionP('ABCDE');
        $publication->setTypeCible('HEBERGEMENT');

        $result = $this->manager->validate($publication);

        $this->assertTrue($result);
    }

    /**
     * Test that publication description exactly at the limit passes
     */
    public function testPublicationWithLongValidDescription(): void
    {
        $publication = new Publication();
        $publication->setDescriptionP('Ceci est une très longue description qui reste parfaitement valide');
        $publication->setTypeCible('ACTIVITE');

        $result = $this->manager->validate($publication);

        $this->assertTrue($result);
    }
}