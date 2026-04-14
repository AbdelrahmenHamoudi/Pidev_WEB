<?php

namespace App\Command;

use App\Entity\Voiture;
use App\Service\CarSpecsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-car-specs',
    description: 'Updates all cars with realistic performance specs using AI logic',
)]
class UpdateCarSpecsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CarSpecsService $specsService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $voitures = $this->entityManager->getRepository(Voiture::class)->findAll();

        $io->progressStart(count($voitures));

        foreach ($voitures as $voiture) {
            $specs = $this->specsService->suggestSpecs($voiture->getMarque(), $voiture->getModele());
            
            $voiture->setPuissance($specs['puissance']);
            $voiture->setVitesseMax($specs['vitesse_max']);
            $voiture->setAcceleration($specs['acceleration']);
            $voiture->setConsommation($specs['consommation']);
            $voiture->setBoiteVitesse($specs['boite_vitesse']);

            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();
        $io->success('Toutes les voitures ont été mises à jour avec des spécifications réalistes !');

        return Command::SUCCESS;
    }
}
