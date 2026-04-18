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
        
        $qb = $voitureRepo->createQueryBuilder('v');

        if ($search) {
            $searchLower = '%' . strtolower($search) . '%';
            $qb->andWhere('LOWER(v.marque) LIKE :search OR LOWER(v.modele) LIKE :search OR LOWER(v.description) LIKE :search')
               ->setParameter('search', $searchLower);
        }

        return $this->render('frontend/voiture/guide.html.twig', [
            'voitures' => $qb->getQuery()->getResult(),
            'search' => $search,
        ]);
    }
}
