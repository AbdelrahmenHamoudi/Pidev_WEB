<?php

namespace App\Command;

use App\Entity\Promotion;
use App\Repository\PromotionRepository;
use App\Service\TrendingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:promotion:smart-engine',
    description: 'Runs the Smart Scheduler (expiration) and Smart Discount engine.',
)]
class PromotionSmartEngineCommand extends Command
{
    public function __construct(
        private PromotionRepository $promoRepo,
        private EntityManagerInterface $em,
        private TrendingService $trendingService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $promotions = $this->promoRepo->findAll();
        $now = new \DateTime();

        $changes = 0;

        foreach ($promotions as $promo) {
            // 1. SMART DISCOUNT ENGINE
            // Start analysis 24h AFTER promo creation
            // Assuming creation date is available, but if not we use start_date
            $ageInHours = ($now->getTimestamp() - $promo->getStartDate()->getTimestamp()) / 3600;

            if ($ageInHours > 24 && $promo->getEndDate() > $now && $promo->getStatus() === Promotion::STATUS_ACTIVE) {
                $views = $promo->getViews();
                $reservations = $this->trendingService->getNbReservations($promo);

                $currentDiscount = $promo->getDiscountPercentage() ?? 0;

                if ($views < 10 && $reservations == 0) {
                    // Low interaction -> Increase discount by 5% (max 50%)
                    if ($currentDiscount < 50) {
                        $newDiscount = min(50, $currentDiscount + 5);
                        $promo->setDiscountPercentage($newDiscount);
                        $promo->setDiscountFixed(null);
                        $io->info(sprintf('Promotion #%d (%s): Increased discount to %d%% due to low engagement.', $promo->getId(), $promo->getName(), $newDiscount));
                        $changes++;
                    }
                } elseif ($views > 50 && $reservations > 5) {
                    // High interaction -> Decrease discount by 2% (min 5%)
                    if ($currentDiscount > 5) {
                        $newDiscount = max(5, $currentDiscount - 2);
                        $promo->setDiscountPercentage($newDiscount);
                        $promo->setDiscountFixed(null);
                        $io->info(sprintf('Promotion #%d (%s): Decreased discount to %d%% due to high demand.', $promo->getId(), $promo->getName(), $newDiscount));
                        $changes++;
                    }
                }
            }

            // 2. SMART SCHEDULER (Simulation for log, real alerts on frontend)
            $hoursToExpiration = ($promo->getEndDate()->getTimestamp() - $now->getTimestamp()) / 3600;
            if ($hoursToExpiration > 0 && $hoursToExpiration <= 24) {
                $io->warning(sprintf('Promotion #%d (%s) will expire in %.1f hours.', $promo->getId(), $promo->getName(), $hoursToExpiration));
            }
        }

        if ($changes > 0) {
            $this->em->flush();
            $io->success(sprintf('%d promotion(s) updated by the Smart Discount Engine.', $changes));
        } else {
            $io->success('No changes made by the Smart Discount Engine.');
        }

        return Command::SUCCESS;
    }
}
