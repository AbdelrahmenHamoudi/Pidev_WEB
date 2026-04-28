<?php

namespace App\Controller;

use App\Service\CarSpecsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CarSpecsController extends AbstractController
{
    #[Route('/api/car-specs', name: 'api_car_specs', methods: ['POST'])]
    public function getSpecs(Request $request, CarSpecsService $specsService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $marque = $data['marque'] ?? '';
        $modele = $data['modele'] ?? '';

        if (empty($marque) || empty($modele)) {
            return new JsonResponse(['error' => 'Marque et modèle requis'], 400);
        }

        $specs = $specsService->suggestSpecs($marque, $modele);

        return new JsonResponse($specs);
    }
}
