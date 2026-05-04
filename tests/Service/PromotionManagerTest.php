<?php

namespace App\Tests\Service;

use App\Entity\Promotion;
use App\Service\PromotionManager;
use PHPUnit\Framework\TestCase;

/**
 
 
 * Pour exécuter : php bin/phpunit tests/Service/PromotionManagerTest.php --testdox
 */
class PromotionManagerTest extends TestCase
{
    
    private PromotionManager $manager;

    /**
     * setUp() est appelé automatiquement AVANT chaque test.
     * On crée une nouvelle instance du service pour chaque test.
     */
    protected function setUp(): void
    {
        $this->manager = new PromotionManager();
    }

    

    /**
     * Crée une promotion avec toutes les données valides.
     * On part de cette base dans chaque test, puis on "casse" UN seul champ
     * pour vérifier que le validateur le détecte.
     */
    private function makePromotion(): Promotion
    {
        $p = new Promotion();
        $p->setName('Summer Deal 2026');
        $p->setDiscountPercentage(20.0);
        $p->setStartDate(new \DateTime('2026-06-01'));
        $p->setEndDate(new \DateTime('2026-06-30'));
        $p->setPromoType('individuelle');
        return $p;
    }

    // ════════════════════════════════════════════════════════════════════════
    // GROUPE 1 : Tests de validation réussie
    // ════════════════════════════════════════════════════════════════════════

    /**
     * TEST 1 — Une promotion complète et correcte doit retourner TRUE.
     *
     * Règle testée : La validation d'une promotion valide ne lance pas d'exception.
     */
    public function testUnePromotionValideRetourneTrue(): void
    {
        $promotion = $this->makePromotion();

        // assertTrue() vérifie que validate() retourne bien true
        $this->assertTrue($this->manager->validate($promotion));
    }

    /**
     * TEST 2 — Une réduction par montant fixe (sans %) est aussi valide.
     *
     * Règle testée : La promotion peut avoir une réduction fixe au lieu du %.
     */
    public function testReductionFixeSeuleEstValide(): void
    {
        $promotion = $this->makePromotion();
        $promotion->setDiscountPercentage(null); // on supprime le %
        $promotion->setDiscountFixed(50.0);      // on met un montant fixe

        $this->assertTrue($this->manager->validate($promotion));
    }

    // ════════════════════════════════════════════════════════════════════════
    // GROUPE 2 : Tests du champ "nom"
    // ════════════════════════════════════════════════════════════════════════

    /**
     * TEST 3 — Un nom vide doit lever une exception.
     *
     * Règle métier : "Le nom de la promotion est obligatoire."
     * expectException() dit à PHPUnit : "ce test réussit UNIQUEMENT si
     * une InvalidArgumentException est lancée."
     */
    public function testNomVideLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la promotion est obligatoire.');

        $promotion = $this->makePromotion();
        $promotion->setName(''); // nom vide → doit provoquer une exception
        $this->manager->validate($promotion);
    }

    /**
     * TEST 4 — Un nom composé uniquement d'espaces doit lever une exception.
     *
     * Règle métier : même règle — trim() détecte les espaces purs.
     */
    public function testNomAvecEspacesSeulementLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de la promotion est obligatoire.');

        $promotion = $this->makePromotion();
        $promotion->setName('     '); // 5 espaces → équivalent à vide après trim()
        $this->manager->validate($promotion);
    }

    // ════════════════════════════════════════════════════════════════════════
    // GROUPE 3 : Tests de la réduction en pourcentage
    // ════════════════════════════════════════════════════════════════════════

    /**
     * TEST 5 — Une réduction de 150% doit lever une exception.
     *
     * Règle métier : "La réduction en pourcentage doit être entre 1% et 100%."
     */
    public function testReductionPourcentageTropEleveeLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La réduction en pourcentage doit être comprise entre 1% et 100%.');

        $promotion = $this->makePromotion();
        $promotion->setDiscountPercentage(150.0); // 150% → invalide
        $this->manager->validate($promotion);
    }

    /**
     * TEST 6 — Une réduction de 0% doit lever une exception.
     *
     * Règle métier : la réduction minimale est 1% (pas 0%).
     */
    public function testReductionPourcentageZeroLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $promotion = $this->makePromotion();
        $promotion->setDiscountPercentage(0.0); // 0% → invalide (minimum = 1%)
        $this->manager->validate($promotion);
    }

    /**
     * TEST 7 — Une réduction négative doit lever une exception.
     *
     * Règle métier : même règle — une réduction ne peut pas être négative.
     */
    public function testReductionPourcentageNegativeLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $promotion = $this->makePromotion();
        $promotion->setDiscountPercentage(-10.0); // -10% → invalide
        $this->manager->validate($promotion);
    }

    /**
     * TEST 8 — Aucune réduction définie (ni % ni fixe) doit lever une exception.
     *
     * Règle métier : "Une promotion doit avoir au moins une réduction."
     */
    public function testAucuneReductionLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Une promotion doit avoir au moins une réduction');

        $promotion = $this->makePromotion();
        $promotion->setDiscountPercentage(null); // pas de %
        $promotion->setDiscountFixed(null);      // pas de fixe non plus
        $this->manager->validate($promotion);
    }

    // ════════════════════════════════════════════════════════════════════════
    // GROUPE 4 : Tests des dates
    // ════════════════════════════════════════════════════════════════════════

    /**
     * TEST 9 — Une date de fin AVANT la date de début doit lever une exception.
     *
     * Règle métier : "La date de fin doit être postérieure à la date de début."
     * C'est la règle la plus importante des dates !
     */
    public function testDateFinAvantDateDebutLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $promotion = $this->makePromotion();
        $promotion->setStartDate(new \DateTime('2026-06-30')); // début = 30 juin
        $promotion->setEndDate(new \DateTime('2026-06-01'));   // fin = 1er juin → AVANT le début !
        $this->manager->validate($promotion);
    }

    /**
     * TEST 10 — Une date de début nulle doit lever une exception.
     *
     * Règle métier : "Les dates de début et de fin sont obligatoires."
     */
    public function testDateDebutNulleLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les dates de début et de fin sont obligatoires.');

        $promotion = $this->makePromotion();
        $promotion->setStartDate(null); // date de début manquante
        $this->manager->validate($promotion);
    }

    /**
     * TEST 11 — Une date de fin nulle doit lever une exception.
     *
     * Règle métier : même règle — les deux dates sont obligatoires.
     */
    public function testDateFinNulleLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les dates de début et de fin sont obligatoires.');

        $promotion = $this->makePromotion();
        $promotion->setEndDate(null); // date de fin manquante
        $this->manager->validate($promotion);
    }

    // ════════════════════════════════════════════════════════════════════════
    // GROUPE 5 : Tests du type pack
    // ════════════════════════════════════════════════════════════════════════

    /**
     * TEST 12 — Un pack sans offres (packItems null) doit lever une exception.
     *
     * Règle métier : "Un pack doit contenir au moins une offre."
     */
    public function testPackSansItemsLeveUneException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Un pack doit contenir au moins une offre.');

        $promotion = $this->makePromotion();
        $promotion->setPromoType('pack'); // type = pack
        $promotion->setPackItems(null);   // mais aucun item → invalide
        $this->manager->validate($promotion);
    }

    /**
     * TEST 13 — Un pack AVEC des offres est valide.
     *
     * Règle métier : si packItems contient du JSON, le pack est valide.
     */
    public function testPackAvecItemsEstValide(): void
    {
        $promotion = $this->makePromotion();
        $promotion->setPromoType('pack');
        // On simule un pack avec 2 offres en JSON
        $promotion->setPackItems('[{"type":"hebergement","id":1},{"type":"voiture","id":3}]');

        $this->assertTrue($this->manager->validate($promotion));
    }
}
