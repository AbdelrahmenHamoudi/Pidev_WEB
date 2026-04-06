<?php

namespace App\Controller\Admin;

use App\Repository\TrajetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/trajets')]
class AdminTrajetController extends AbstractController
{
    #[Route('', name: 'app_admin_trajet_index', methods: ['GET'])]
    public function index(TrajetRepository $trajetRepo): Response
    {
        $trajets = $trajetRepo->findAll();

        return $this->render('backend/trajet/index.html.twig', [
            'trajets' => $trajets,
        ]);
    }
}
