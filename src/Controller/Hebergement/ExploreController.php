<?php

namespace App\Controller\Hebergement;

use App\Repository\HebergementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ExploreController
 *
 * Skyscanner-inspired "Explore" page:
 *
 *  GET  /explore
 *       → Renders the interactive map page (Twig).
 *
 *  GET  /api/hebergements
 *       → JSON API consumed by the Leaflet/JS frontend.
 *         Returns all accommodations with extracted city info.
 */
final class ExploreController extends AbstractController
{
    private const TUNISIAN_CITIES = [
        'tunis','ariana','ben arous','manouba','nabeul','zaghouan','bizerte',
        'béja','beja','jendouba','le kef','kef','siliana','sousse','monastir','mahdia',
        'sfax','kairouan','kasserine','sidi bouzid','gabès','gabes','medenine',
        'tataouine','gafsa','tozeur','kébili','kebili','hammamet','djerba','tabarka',
        'gammarth','la marsa','sidi bou said','carthage','yasmine','port el kantaoui',
        'midoun','houmt souk','douz','el jem','lac','les berges du lac',
        'ennasr','menzah','manar','centre ville','medina','bardo','sfax',
        'la goulette','radès','rades','elyssa','carthage land','salammbo',
        'dar chaabane','korba','kelibia','nabeul','hammam sousse',
    ];

    public function __construct(
        private HebergementRepository $hebergementRepository
    ) {}

    // =====================================================================
    // PAGE — renders the interactive Explore map view
    // =====================================================================

    #[Route('/explore', name: 'app_explore', methods: ['GET'])]
    public function page(): Response
    {
        return $this->render('frontend/hebergement/explore.html.twig');
    }

    // =====================================================================
    // API — returns all accommodations as JSON for the Explore map
    // =====================================================================

    #[Route('/api/hebergements', name: 'api_hebergements', methods: ['GET'])]
    public function apiHebergements(): JsonResponse
    {
        try {
            $hebergements = $this->hebergementRepository->findAll();

            $data = array_map(function ($h) {
                $city = $this->extractCity($h->getTitre());

                return [
                    'id'               => $h->getId(),
                    'titre'            => $h->getTitre(),
                    'type'             => $h->getTypeHebergement(),
                    'capacite'         => $h->getCapacite(),
                    'prix'             => (float) $h->getPrixParNuit(),
                    'image'            => $h->getImage(),
                    'ville'            => $city,
                    'disponible'       => $h->isDisponibleHeberg(),
                ];
            }, $hebergements);

            return $this->json([
                'success' => true,
                'count'   => count($data),
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des hébergements.',
                'debug'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // =====================================================================
    // PRIVATE — extract city name from hebergement title
    // =====================================================================

    private function extractCity(string $titre): string
    {
        $lower = mb_strtolower($titre, 'UTF-8');

        foreach (self::TUNISIAN_CITIES as $city) {
            if (str_contains($lower, mb_strtolower($city, 'UTF-8'))) {
                return ucwords($city);
            }
        }

        return 'Tunisie';
    }
}
