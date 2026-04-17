<?php

namespace App\Service;

use App\Entity\Hebergement;
use App\Entity\Voiture;
use App\Entity\Activite;
use App\Entity\Promotion;

/**
 * Service de calcul de prix — Module Promotion RE7LA
 *
 * Règles métier :
 *   Hébergement → prix_par_nuit  × nombre_de_nuits
 *   Voiture     → prix_km        × distance_km
 *   Activité    → prix_par_pers. × nb_personnes
 *
 * Si une Promotion active est passée, la réduction est appliquée
 * (% ou montant fixe selon ce qui est renseigné dans la promo).
 */
class PriceCalculatorService
{
    // ─────────────────────────────────────────────────────────────
    // HÉBERGEMENT
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   type: string,
     *   unit_price: float,
     *   quantity: int,
     *   quantity_label: string,
     *   base_price: float,
     *   discount: float,
     *   final_price: float,
     *   currency: string
     * }
     */
    public function calculateHebergementPrice(
        Hebergement $hebergement,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?Promotion $promotion = null
    ): array {
        $nights = (int) $dateDebut->diff($dateFin)->days;

        if ($nights < 1) {
            throw new \InvalidArgumentException('La durée doit être d\'au moins 1 nuit.');
        }

        // prix_par_nuit est stocké en varchar dans la BD → cast en float
        $pricePerNight = (float) $hebergement->getPrixParNuit();
        $basePrice     = round($pricePerNight * $nights, 2);
        $discount      = $this->computeDiscount($basePrice, $promotion);
        $finalPrice    = max(0.0, round($basePrice - $discount, 2));

        return [
            'type'           => 'hebergement',
            'unit_price'     => $pricePerNight,
            'quantity'       => $nights,
            'quantity_label' => $nights . ' nuit' . ($nights > 1 ? 's' : ''),
            'base_price'     => $basePrice,
            'discount'       => $discount,
            'final_price'    => $finalPrice,
            'currency'       => 'DT',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // VOITURE
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   type: string,
     *   unit_price: float,
     *   quantity: float,
     *   quantity_label: string,
     *   base_price: float,
     *   discount: float,
     *   final_price: float,
     *   currency: string
     * }
     */
    public function calculateVoiturePrice(
        Voiture $voiture,
        float $distanceKm,
        ?Promotion $promotion = null
    ): array {
        if ($distanceKm <= 0) {
            throw new \InvalidArgumentException('La distance doit être positive.');
        }

        $pricePerKm = (float) $voiture->getPrix_km();
        $basePrice  = round($pricePerKm * $distanceKm, 2);
        $discount   = $this->computeDiscount($basePrice, $promotion);
        $finalPrice = max(0.0, round($basePrice - $discount, 2));

        return [
            'type'           => 'voiture',
            'unit_price'     => $pricePerKm,
            'quantity'       => $distanceKm,
            'quantity_label' => $distanceKm . ' km',
            'base_price'     => $basePrice,
            'discount'       => $discount,
            'final_price'    => $finalPrice,
            'currency'       => 'DT',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // ACTIVITÉ
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   type: string,
     *   unit_price: float,
     *   quantity: int,
     *   quantity_label: string,
     *   base_price: float,
     *   discount: float,
     *   final_price: float,
     *   currency: string
     * }
     */
    public function calculateActivitePrice(
        Activite $activite,
        int $nbPersonnes,
        ?Promotion $promotion = null
    ): array {
        if ($nbPersonnes < 1) {
            throw new \InvalidArgumentException('Le nombre de personnes doit être ≥ 1.');
        }

        if ($nbPersonnes > $activite->getCapaciteMax()) {
            throw new \InvalidArgumentException(sprintf(
                'Capacité maximale dépassée : %d place(s) disponible(s) au maximum.',
                $activite->getCapaciteMax()
            ));
        }

        // prixParPersonne est stocké en varchar dans la BD → cast en float
        $pricePerPerson = (float) $activite->getPrixParPersonne();
        $basePrice      = round($pricePerPerson * $nbPersonnes, 2);
        $discount       = $this->computeDiscount($basePrice, $promotion);
        $finalPrice     = max(0.0, round($basePrice - $discount, 2));

        return [
            'type'           => 'activite',
            'unit_price'     => $pricePerPerson,
            'quantity'       => $nbPersonnes,
            'quantity_label' => $nbPersonnes . ' personne' . ($nbPersonnes > 1 ? 's' : ''),
            'base_price'     => $basePrice,
            'discount'       => $discount,
            'final_price'    => $finalPrice,
            'currency'       => 'DT',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVÉ — calcul de la réduction
    // ─────────────────────────────────────────────────────────────

    private function computeDiscount(float $basePrice, ?Promotion $promotion): float
    {
        if ($promotion === null || !$promotion->isActive()) {
            return 0.0;
        }

        if ($promotion->getDiscountPercentage() !== null) {
            return round($basePrice * ($promotion->getDiscountPercentage() / 100), 2);
        }

        if ($promotion->getDiscountFixed() !== null) {
            // La réduction fixe ne peut pas dépasser le prix de base
            return min($basePrice, (float) $promotion->getDiscountFixed());
        }

        return 0.0;
    }
}