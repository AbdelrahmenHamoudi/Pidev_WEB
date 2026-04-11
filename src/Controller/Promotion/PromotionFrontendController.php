<?php

namespace App\Controller\Promotion;

use App\Repository\PromotionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PromotionFrontendController extends AbstractController
{
    #[Route('/promotions', name: 'app_promotions_list')]
    public function list(PromotionRepository $repo): Response
    {
        $promotions = $repo->findAll();

        return $this->render('frontend/promotion/list.html.twig', [
            'promotions' => $promotions,
        ]);
    }
}