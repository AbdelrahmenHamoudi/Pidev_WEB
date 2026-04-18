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
        private \App\Service\SmartDiscountService $smartDiscountService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Smart Discount Engine');
        
        $changes = $this->smartDiscountService->runEngine();

        if ($changes > 0) {
            $io->success(sprintf('%d promotion(s) updated by the Smart Discount Engine.', $changes));
        } else {
            $io->info('No changes made by the Smart Discount Engine.');
        }

        return Command::SUCCESS;
    }
}
