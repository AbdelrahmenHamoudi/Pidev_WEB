<?php

namespace App\Controller;

use App\Entity\Planning;
use App\Form\PlanningType;
use App\Service\ActiviteService;
use App\Service\PlanningService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/plannings')]
class PlanningController extends AbstractController
{
    public function __construct(
        private PlanningService $planningService,
        private ActiviteService $activiteService,
    ) {}

    // ── Liste plannings d'une activite ─────────────────────────────────────

    #[Route('/activite/{idActivite}', name: 'planning_index', methods: ['GET'])]
    public function index(int $idActivite): Response
    {
        $activite  = $this->activiteService->findById($idActivite);
        $plannings = $this->planningService->findDisponiblesByActivite($idActivite);
        $dates     = $this->planningService->getDatesDisponibles($idActivite);

        return $this->render('planning/index.html.twig', [
            'activite'  => $activite,
            'plannings' => $plannings,
            'dates'     => $dates,
        ]);
    }

    // ── Creer ──────────────────────────────────────────────────────────────

    #[Route('/new', name: 'planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $planning = new Planning();
        $form     = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->planningService->create($planning);
            $this->addFlash('success', 'Planning cree avec succes !');
            return $this->redirectToRoute('planning_index', [
                'idActivite' => $planning->getActivite()->getIdActivite()
            ]);
        }

        return $this->render('planning/new.html.twig', ['form' => $form]);
    }

    // ── Modifier ───────────────────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'planning_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $planning = $this->planningService->findById($id);

        if (!$planning) {
            throw $this->createNotFoundException("Planning #$id introuvable");
        }

        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->planningService->update($planning);
            $this->addFlash('success', 'Planning modifie avec succes !');
            return $this->redirectToRoute('planning_index', [
                'idActivite' => $planning->getActivite()->getIdActivite()
            ]);
        }

        return $this->render('planning/edit.html.twig', [
            'form'     => $form,
            'planning' => $planning,
        ]);
    }

    // ── Supprimer ──────────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'planning_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $planning = $this->planningService->findById($id);

        if (!$planning) {
            throw $this->createNotFoundException("Planning #$id introuvable");
        }

        $idActivite = $planning->getActivite()->getIdActivite();

        if ($this->isCsrfTokenValid('delete_planning_' . $id, $request->request->get('_token'))) {
            $this->planningService->delete($planning);
            $this->addFlash('success', 'Planning supprime.');
        } else {
            $this->addFlash('error', 'Token de securite invalide.');
        }

        return $this->redirectToRoute('planning_index', ['idActivite' => $idActivite]);
    }

    // ── Reservation ────────────────────────────────────────────────────────

    #[Route('/{id}/reserver', name: 'planning_reserver', methods: ['POST'])]
    public function reserver(int $id, Request $request): Response
    {
        $planning = $this->planningService->findById($id);

        if (!$planning) {
            throw $this->createNotFoundException("Planning #$id introuvable");
        }

        $idActivite = $planning->getActivite()->getIdActivite();

        if ($this->isCsrfTokenValid('reserver_' . $id, $request->request->get('_token'))) {
            $success = $this->planningService->reserverPlace($id);
            if ($success) {
                $this->addFlash('success', 'Reservation confirmee ! Vous recevrez un email de confirmation.');
            } else {
                $this->addFlash('error', 'Plus de places disponibles pour ce planning.');
            }
        } else {
            $this->addFlash('error', 'Token de securite invalide.');
        }

        return $this->redirectToRoute('activite_show', ['id' => $idActivite]);
    }
}
