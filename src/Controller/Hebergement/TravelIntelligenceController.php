<?php

namespace App\Controller\Hebergement;

use App\Service\Hebergement\TravelIntelligenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TravelIntelligenceController extends AbstractController
{
    #[Route('/intelligence/best-time/{city}', name: 'app_best_time')]
    public function bestTime(string $city, TravelIntelligenceService $intelligenceService): Response
    {
        $analysis = $intelligenceService->getCityAnalysis($city);

        return $this->render('frontend/intelligence/best_time.html.twig', [
            'analysis' => $analysis,
            'other_cities' => ['Tunis', 'Sousse', 'Djerba']
        ]);
    }

    #[Route('/intelligence/matching', name: 'app_matching_quiz')]
    public function matchingQuiz(): Response
    {
        return $this->render('frontend/intelligence/matching.html.twig', [
            'step' => 'quiz'
        ]);
    }

    #[Route('/intelligence/matching/result', name: 'app_matching_result', methods: ['POST'])]
    public function matchingResult(Request $request, TravelIntelligenceService $intelligenceService): Response
    {
        $budget = $request->request->get('budget');
        $ambiance = $request->request->get('ambiance');
        $duration = $request->request->get('duration');

        if (!$budget || !$ambiance || !$duration) {
            return $this->redirectToRoute('app_matching_quiz');
        }

        $results = $intelligenceService->getMatchingDestinations([
            'budget' => $budget,
            'ambiance' => $ambiance,
            'duration' => $duration
        ]);

        return $this->render('frontend/intelligence/matching.html.twig', [
            'step' => 'result',
            'top' => $results['top'],
            'alternatives' => $results['alternatives'],
            'criteria' => [
                'budget' => $budget,
                'ambiance' => $ambiance,
                'duration' => $duration
            ]
        ]);
    }
}
