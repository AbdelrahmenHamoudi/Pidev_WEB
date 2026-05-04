<?php

namespace App\Service;

use App\Entity\Promotion;


class PromotionManager
{

    public function validate(Promotion $promotion): bool
    {
        // ── Règle 1 : Le nom est obligatoire ─────────────────────────────────
        if (empty(trim($promotion->getName()))) {
            throw new \InvalidArgumentException('Le nom de la promotion est obligatoire.');
        }

        // ── Règle 2 : La réduction % doit être entre 1 et 100 ───────────────
        $pct = $promotion->getDiscountPercentage();
        if ($pct !== null && ($pct < 1 || $pct > 100)) {
            throw new \InvalidArgumentException(
                'La réduction en pourcentage doit être comprise entre 1% et 100%.'
            );
        }

        // ── Règle 3 : Au moins une réduction doit être définie ───────────────
        if ($pct === null && $promotion->getDiscountFixed() === null) {
            throw new \InvalidArgumentException(
                'Une promotion doit avoir au moins une réduction (% ou montant fixe).'
            );
        }

        // ── Règle 4 : Les dates sont obligatoires ────────────────────────────
        if ($promotion->getStartDate() === null || $promotion->getEndDate() === null) {
            throw new \InvalidArgumentException(
                'Les dates de début et de fin sont obligatoires.'
            );
        }

        // ── Règle 5 : La date de fin doit être après la date de début ────────
        if ($promotion->getEndDate() <= $promotion->getStartDate()) {
            throw new \InvalidArgumentException(
                'La date de fin doit être postérieure à la date de début.'
            );
        }

        // ── Règle 6 : Un pack doit avoir des offres ──────────────────────────
        if ($promotion->isPack() && empty($promotion->getPackItems())) {
            throw new \InvalidArgumentException(
                'Un pack doit contenir au moins une offre.'
            );
        }

        return true;
    }
}
