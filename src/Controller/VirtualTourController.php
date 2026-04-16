<?php
namespace App\Controller;

use App\Service\VirtualTourService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/visite-virtuelle')]
class VirtualTourController extends AbstractController
{
    public function __construct(
        private VirtualTourService $tourService
    ) {}

    // ── Page de génération (appel AJAX) ───────────────────────────────────
    #[Route('/generer/{lieu}', name: 'virtual_tour_generer', methods: ['GET'])]
    public function generer(string $lieu): Response
    {
        return $this->render('frontOffice/virtual_tour/loading.html.twig', [
            'lieu' => $lieu,
        ]);
    }

    // ── API endpoint — génère et retourne JSON ────────────────────────────
    #[Route('/api/{lieu}', name: 'virtual_tour_api', methods: ['GET'])]
    public function api(string $lieu): Response
    {
        $data = $this->tourService->generer($lieu);

        return $this->json([
            'success'   => true,
            'lieu'      => $data['lieu'],
            'photos'    => $data['photos'],
            'narration' => $data['narration'],
            'audio'     => $data['audio'],
        ]);
    }
}