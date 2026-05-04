<?php

namespace App\Tests\Service;

use App\Entity\Promotion;
use App\Service\TrendingService;
use App\Repository\ReservationPromoRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le service TrendingService.
 *
 * IMPORTANT : Ce service a besoin d'un ReservationPromoRepository.
 * On utilise un "mock" (faux objet) pour simuler le repository
 * SANS avoir besoin d'une vraie base de données.
 *
 * Constantes de seuil dans TrendingService :
 *   SEUIL_VUES         = 10   → il faut STRICTEMENT PLUS de 10 vues
 *   SEUIL_RESERVATIONS = 5    → il faut STRICTEMENT PLUS de 5 réservations
 *   POIDS_VUES         = 0.4  → coefficient pour le score
 *   POIDS_RESA         = 0.6  → coefficient pour le score
 *   Formule score      = (vues × 0.4) + (réservations × 0.6)
 *
 * Pour exécuter : php bin/phpunit tests/Service/TrendingServiceTest.php --testdox
 */
class TrendingServiceTest extends TestCase
{
    // ════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Crée un TrendingService avec un nombre fixe de réservations simulées.
     *
     * EXPLICATION DU MOCK :
     *   - TrendingService a besoin d'un ReservationPromoRepository (qui touche la BDD)
     *   - On crée un "mock" = un faux objet qui prétend être ce repository
     *   - On dit au mock : "quand getNbReservations() est appelé, retourne X"
     *   - Résultat : nos tests n'ont JAMAIS besoin d'une vraie BDD !
     *
     * @param int $nbReservationsFixes  Nombre fixe de réservations à simuler
     */
    private function creerServiceAvecReservations(int $nbReservationsFixes): TrendingService
    {
        // 1) Créer le faux repository (aucune vraie BDD)
        $repoMock = $this->createMock(ReservationPromoRepository::class);

        // 2) Créer un TrendingService "partiel" qui surcharge getNbReservations()
        //    La méthode getNbVues() reste réelle (elle lit $promotion->getViews())
        $service = $this->getMockBuilder(TrendingService::class)
            ->setConstructorArgs([$repoMock])
            ->onlyMethods(['getNbReservations'])
            ->getMock();

        // 3) Définir ce que retourne getNbReservations() dans nos tests
        $service->method('getNbReservations')
                ->willReturn($nbReservationsFixes);

        return $service;
    }

    /**
     * Crée une promotion avec un nombre de vues défini.
     * Les vues sont stockées dans l'entité elle-même (champ $views).
     */
    private function creerPromotionAvecVues(int $nbVues): Promotion
    {
        $p = new Promotion();
        $p->setName('Promo Test');
        $p->setViews($nbVues);
        return $p;
    }

    // ════════════════════════════════════════════════════════════════════════
    // GROUPE 1 : Tests de isTrending()
    // ════════════════════════════════════════════════════════════════════════

    /**
     * TEST 1 — isTrending() doit retourner TRUE quand vues > 10 ET résa > 5.
     *
     * Règle métier : Une promotion est "tendance" si elle dépasse LES DEUX seuils.
     * Ici : 15 vues > 10 ✓  ET  8 réservations > 5 ✓  → trending = true
     */
    public function testIsTrendingRetourneTrueQuandDeuxSeuilsSontDepasses(): void
    {
        $service = $this->creerServiceAvecReservations(8);   // 8 résa > seuil de 5 ✓
        $promo   = $this->creerPromotionAvecVues(15);        // 15 vues > seuil de 10 ✓

        // assertTrue() : le test réussit si isTrending() retourne true
        $this->assertTrue($service->isTrending($promo));
    }

    /**
     * TEST 2 — isTrending() doit retourner FALSE si les vues sont insuffisantes.
     *
     * Règle métier : Les DEUX seuils doivent être dépassés. Ici seul le seuil
     * des réservations est dépassé, mais pas celui des vues.
     * Ici : 5 vues ≤ 10 ✗  → trending = false même si 20 résa > 5
     */
    public function testIsTrendingRetourneFalseSiVuesInsuffisantes(): void
    {
        $service = $this->creerServiceAvecReservations(20);  // 20 résa > 5 ✓ mais...
        $promo   = $this->creerPromotionAvecVues(5);         // 5 vues ≤ 10 ✗

        // assertFalse() : le test réussit si isTrending() retourne false
        $this->assertFalse($service->isTrending($promo));
    }

    /**
     * TEST 3 — isTrending() doit retourner FALSE si les réservations sont insuffisantes.
     *
     * Règle métier : même raisonnement — les vues sont ok mais pas les réservations.
     * Ici : 50 vues > 10 ✓  MAIS  3 résa ≤ 5 ✗  → trending = false
     */
    public function testIsTrendingRetourneFalseSiReservationsInsuffisantes(): void
    {
        $service = $this->creerServiceAvecReservations(3);   // 3 résa ≤ 5 ✗
        $promo   = $this->creerPromotionAvecVues(50);        // 50 vues > 10 ✓ mais...

        $this->assertFalse($service->isTrending($promo));
    }

    /**
     * TEST 4 — isTrending() doit retourner FALSE EXACTEMENT au seuil (pas inclus).
     *
     * Règle métier : La condition utilise ">" (strictement supérieur), PAS ">=".
     * Donc 10 vues exactement et 5 réservations exactement = PAS trending.
     * Ici : 10 vues = seuil (non dépassé) ET 5 résa = seuil (non dépassé) → false
     */
    public function testIsTrendingRetourneFalseAuSeuilExact(): void
    {
        $service = $this->creerServiceAvecReservations(5);  // exactement 5 = seuil non dépassé
        $promo   = $this->creerPromotionAvecVues(10);       // exactement 10 = seuil non dépassé

        $this->assertFalse($service->isTrending($promo));
    }

    /**
     * TEST 5 — isTrending() doit retourner FALSE si tout est à zéro.
     *
     * Règle métier : une promotion sans vues et sans réservations n'est pas trending.
     */
    public function testIsTrendingRetourneFalseQuandToutEstAZero(): void
    {
        $service = $this->creerServiceAvecReservations(0);
        $promo   = $this->creerPromotionAvecVues(0);

        $this->assertFalse($service->isTrending($promo));
    }

    // ════════════════════════════════════════════════════════════════════════
    // GROUPE 2 : Tests de getTrendingScore()
    // ════════════════════════════════════════════════════════════════════════

    /**
     * TEST 6 — Le score doit être calculé correctement avec la formule.
     *
     * Formule : score = (vues × 0.4) + (réservations × 0.6)
     * Calcul attendu : (20 × 0.4) + (10 × 0.6) = 8.0 + 6.0 = 14.0
     */
    public function testGetTrendingScoreCalculeCorrectement(): void
    {
        $service = $this->creerServiceAvecReservations(10); // 10 réservations
        $promo   = $this->creerPromotionAvecVues(20);       // 20 vues

        // Calcul attendu : (20 × 0.4) + (10 × 0.6) = 8.0 + 6.0 = 14.0
        $scoreAttendu = 14.0;

        // assertEqualsWithDelta() accepte une petite différence (0.001) due aux flottants
        $this->assertEqualsWithDelta(
            $scoreAttendu,
            $service->getTrendingScore($promo),
            0.001 // tolérance pour les calculs en virgule flottante
        );
    }

    /**
     * TEST 7 — Le score doit être 0 si vues et réservations sont à 0.
     *
     * Calcul : (0 × 0.4) + (0 × 0.6) = 0.0
     */
    public function testGetTrendingScoreEstZeroSansActivite(): void
    {
        $service = $this->creerServiceAvecReservations(0);
        $promo   = $this->creerPromotionAvecVues(0);

        $this->assertEqualsWithDelta(0.0, $service->getTrendingScore($promo), 0.001);
    }

    // ════════════════════════════════════════════════════════════════════════
    // GROUPE 3 : Tests de getScoreLabel()
    // ════════════════════════════════════════════════════════════════════════

    /**
     * TEST 8 — Le label doit être "Viral" pour un score >= 50.
     *
     * Calcul : (100 vues × 0.4) + (20 résa × 0.6) = 40 + 12 = 52 → >= 50 → "Viral"
     */
    public function testGetScoreLabelRetourneViralPourScoreEleve(): void
    {
        $service = $this->creerServiceAvecReservations(20); // 20 résa → +12
        $promo   = $this->creerPromotionAvecVues(100);      // 100 vues → +40 → score = 52

        // assertStringContainsString() vérifie que la chaîne contient "Viral"
        $this->assertStringContainsString('Viral', $service->getScoreLabel($promo));
    }

    /**
     * TEST 9 — Le label doit être "Dormant" pour un score très faible (< 3).
     *
     * Calcul : (1 vue × 0.4) + (0 résa × 0.6) = 0.4 → < 3 → "Dormant"
     */
    public function testGetScoreLabelRetourneDormantPourScoreTresFaible(): void
    {
        $service = $this->creerServiceAvecReservations(0); // 0 résa
        $promo   = $this->creerPromotionAvecVues(1);       // 1 vue → score = 0.4

        $this->assertStringContainsString('Dormant', $service->getScoreLabel($promo));
    }

    // ════════════════════════════════════════════════════════════════════════
    // GROUPE 4 : Tests de countTrending()
    // ════════════════════════════════════════════════════════════════════════

    /**
     * TEST 10 — countTrending() doit compter correctement les promos tendance.
     *
     * On crée 3 promotions fictives :
     *   - promo1 → trending = true
     *   - promo2 → trending = false
     *   - promo3 → trending = true
     * Résultat attendu : 2
     *
     * Note : On utilise un mock de isTrending() pour ne pas re-tester cette méthode.
     */
    public function testCountTrendingCompteCorrectement(): void
    {
        // Créer le faux repository
        $repoMock = $this->createMock(ReservationPromoRepository::class);

        // Créer un service où on contrôle ce que isTrending() retourne
        $service = $this->getMockBuilder(TrendingService::class)
            ->setConstructorArgs([$repoMock])
            ->onlyMethods(['isTrending'])
            ->getMock();

        // Créer 3 promotions anonymes
        $promo1 = new Promotion();
        $promo2 = new Promotion();
        $promo3 = new Promotion();

        // Définir le comportement : promo1 et promo3 sont trending, promo2 ne l'est pas
        $service->method('isTrending')
                ->willReturnMap([
                    [$promo1, true],   // promo1 → trending
                    [$promo2, false],  // promo2 → pas trending
                    [$promo3, true],   // promo3 → trending
                ]);

        // Vérifier que le comptage retourne 2
        $resultat = $service->countTrending([$promo1, $promo2, $promo3]);
        $this->assertEquals(2, $resultat);
    }

    // ════════════════════════════════════════════════════════════════════════
    // GROUPE 5 : Tests de getNbVues()
    // ════════════════════════════════════════════════════════════════════════

    /**
     * TEST 11 — getNbVues() doit retourner le nombre de vues de l'entité.
     *
     * Cette méthode lit simplement $promotion->getViews().
     * On vérifie que la transmission de valeur est correcte.
     */
    public function testGetNbVuesRetourneLaValeurDeViews(): void
    {
        // Pour ce test, on n'a pas besoin de simuler les réservations
        // car getNbVues() ne touche pas au repository
        $repoMock = $this->createMock(ReservationPromoRepository::class);
        $service  = new TrendingService($repoMock); // service RÉEL (pas de mock partiel)

        $promo = $this->creerPromotionAvecVues(42); // 42 vues dans l'entité

        // Vérifier que getNbVues() retourne bien 42
        $this->assertEquals(42, $service->getNbVues($promo));
    }

    /**
     * TEST 12 — getNbVues() doit retourner 0 par défaut si pas de vues.
     */
    public function testGetNbVuesRetourneZeroParDefaut(): void
    {
        $repoMock = $this->createMock(ReservationPromoRepository::class);
        $service  = new TrendingService($repoMock);

        $promo = new Promotion(); // nouvelle promo : views = 0 par défaut

        $this->assertEquals(0, $service->getNbVues($promo));
    }
}
