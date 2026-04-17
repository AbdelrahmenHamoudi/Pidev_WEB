<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontVoitureController extends AbstractController
{
    #[Route('/voiture/guide', name: 'app_voiture_guide')]
    public function guide(Request $request, \App\Repository\VoitureRepository $voitureRepo): Response
    {
        $search = $request->query->get('search', '');
        
        $qb = $voitureRepo->createQueryBuilder('v')
            ->where('v.disponibilite = :dispo')
            ->setParameter('dispo', true);

        if ($search) {
            $qb->andWhere('v.marque LIKE :search OR v.modele LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $this->render('frontend/voiture/guide.html.twig', [
            'voitures' => $qb->getQuery()->getResult(),
            'search' => $search,
        ]);
    }
}
