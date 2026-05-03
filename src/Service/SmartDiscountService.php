<?php

namespace App\Service;

use App\Entity\Promotion;
use App\Repository\PromotionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\TrendingService;
use App\Service\PusherService;

class SmartDiscountService
{
    public function __construct(
        private PromotionRepository $promoRepo,
        private EntityManagerInterface $em,
        private TrendingService $trendingService,
        private PusherService $pusherService
    ) {}

    /**
     * Executes the smart discount engine logic on all active promotions.
     * Returns the number of changes made.
     */
    public function runEngine(): int
    {
        $promotions = $this->promoRepo->findAll();
        $now = new \DateTime();
        $changes = 0;

        foreach ($promotions as $promo) {
            // Logic: Start analysis 24h AFTER promo creation
            if (!$promo->getCreatedAt()) {
                continue;
            }

            $ageInHours = ($now->getTimestamp() - $promo->getCreatedAt()->getTimestamp()) / 3600;

            // Cooldown: only check if never checked OR checked more than 12h ago
            $lastCheck = $promo->getLastEngineCheck();
            $cooldownPassed = !$lastCheck || (($now->getTimestamp() - $lastCheck->getTimestamp()) / 3600 > 12);

            if ($ageInHours > 1 && $promo->getStatus() === Promotion::STATUS_ACTIVE && $cooldownPassed) {
                $views = $promo->getViews();
                $reservations = $this->trendingService->getNbReservations($promo);

                $currentDiscount = $promo->getDiscountPercentage() ?? 0;

                if ($views < 10 && $reservations == 0) {
                    // Low interaction -> Increase discount by 5% (max 50%)
                    if ($currentDiscount < 50) {
                        $newDiscount = min(50, $currentDiscount + 5);
                        $this->applyAdjustment($promo, $newDiscount, 'low_engagement', $now);
                        
                        // Notify users via Pusher
                        $this->pusherService->trigger('promotions', 'smart-increase', [
                            'id' => $promo->getId(),
                            'name' => $promo->getName(),
                            'newDiscount' => $newDiscount . '%',
                            'message' => 'Discount increased due to low engagement'
                        ]);
                        
                        $changes++;
                    }
                } elseif ($views > 50 && $reservations > 5) {
                    // High interaction -> Decrease discount by 2% (min 5%)
                    if ($currentDiscount > 5) {
                        $newDiscount = max(5, $currentDiscount - 2);
                        $this->applyAdjustment($promo, $newDiscount, 'high_demand', $now);
                        $changes++;
                    }
                } else {
                    // Mark as checked even if no change to respect cooldown
                    $promo->setLastEngineCheck($now);
                }
            }
        }

        if ($changes > 0) {
            $this->em->flush();
        }

        return $changes;
    }

    private function applyAdjustment(Promotion $promo, int $newDiscount, string $reason, \DateTime $now): void
    {
        if ($promo->getOriginalDiscountPercentage() === null) {
            $promo->setOriginalDiscountPercentage($promo->getDiscountPercentage());
        }
        $promo->setDiscountPercentage($newDiscount);
        $promo->setDiscountFixed(null);
        $promo->setAutoDiscountReason($reason);
        $promo->setLastEngineCheck($now);
    }
}
