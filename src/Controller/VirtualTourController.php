<?php
namespace App\Controller;

use App\Service\VirtualTourService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/visite-virtuelle')]
class VirtualTourController extends AbstractController
{
    public function __construct(
        private VirtualTourService $tourService
    ) {}

    // ── Page de sélection de lieu ─────────────────────────────────────────
    #[Route('', name: 'virtual_tour_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('frontOffice/virtual_tour/index.html.twig', [
            'lieux' => $this->tourService->getLieuxAutorises(),
        ]);
    }

    // ── Page de génération (loading + AJAX) ───────────────────────────────
    #[Route('/generer/{lieu}', name: 'virtual_tour_generer', methods: ['GET'])]
    public function generer(string $lieu, Request $request): Response
    {
        // Validation sécurité
        if (!$this->tourService->estLieuAutorise($lieu)) {
            throw $this->createNotFoundException('Lieu non disponible : ' . htmlspecialchars($lieu));
        }

        $langue = $request->query->get('langue', 'français');
        $languesAutorisees = ['français', 'arabe'];
        if (!in_array($langue, $languesAutorisees)) {
            $langue = 'français';
        }

        return $this->render('frontOffice/virtual_tour/loading.html.twig', [
            'lieu'   => $lieu,
            'langue' => $langue,
        ]);
    }

    // ── API endpoint — génère et retourne JSON ────────────────────────────
    #[Route('/api/{lieu}', name: 'virtual_tour_api', methods: ['GET'])]
    public function api(string $lieu, Request $request): JsonResponse
    {
        // Validation sécurité
        if (!$this->tourService->estLieuAutorise($lieu)) {
            return $this->json(['success' => false, 'error' => 'Lieu non autorisé.'], 400);
        }

        $langue = $request->query->get('langue', 'français');
        $languesAutorisees = ['français', 'arabe'];
        if (!in_array($langue, $languesAutorisees)) {
            $langue = 'français';
        }

        $data = $this->tourService->generer($lieu, $langue);

        return $this->json([
            'success'   => true,
            'lieu'      => $data['lieu'],
            'photos'    => $data['photos'],
            'narration' => $data['narration'],
            'audio'     => $data['audio'],
            'langue'    => $data['langue'],
        ]);
    }

    // ── API Quiz — retourne les questions culturelles ─────────────────────
    #[Route('/api/quiz/{lieu}', name: 'virtual_tour_quiz', methods: ['GET'])]
    public function quiz(string $lieu, Request $request): JsonResponse
    {
        if (!$this->tourService->estLieuAutorise($lieu)) {
            return $this->json(['success' => false, 'error' => 'Lieu non autorisé.'], 400);
        }

        $langue = $request->query->get('langue', 'français');
        $languesAutorisees = ['français', 'arabe'];
        if (!in_array($langue, $languesAutorisees)) {
            $langue = 'français';
        }

        $quiz = $this->tourService->genererQuiz($lieu, $langue);

        return $this->json([
            'success' => true,
            'quiz'    => $quiz,
        ]);
    }
    #[Route('/api/audio/{lieu}', name: 'virtual_tour_audio', methods: ['GET'])]
public function audio(string $lieu, Request $request): JsonResponse
{
    set_time_limit(120); // audio peut prendre jusqu'à 60s

    if (!$this->tourService->estLieuAutorise($lieu)) {
        return $this->json(['success' => false], 400);
    }

    $langue    = $request->query->get('langue', 'français');
    $narration = $this->tourService->getNarration($lieu, $langue); // depuis le cache
    $audioPath = $this->tourService->genererAudioPublic($narration, $langue);

    return $this->json([
        'success' => true,
        'audio'   => $audioPath,
    ]);
}
}