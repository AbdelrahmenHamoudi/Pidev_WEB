<?php

namespace App\Controller;

use App\Entity\Planningactivite;
use App\Form\PlanningType;
use App\Service\ActiviteService;
use App\Service\PlanningService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ReservationActiviteService;

#[Route('/plannings')]
class PlanningController extends AbstractController
{
    public function __construct(
        private PlanningService $planningService,
        private ActiviteService $activiteService,
    ) {}

    #[Route('/activite/{idActivite}', name: 'planning_index', methods: ['GET'])]
    public function index(int $idActivite): Response
    {
        $activite  = $this->activiteService->findById($idActivite);
        $plannings = $this->planningService->findDisponiblesByActivite($idActivite);
        $dates     = $this->planningService->getDatesDisponibles($idActivite);
        $stats     = $this->planningService->getStats($idActivite);

        return $this->render('backOffice/admin/planning/index.html.twig', [
            'activite'  => $activite,
            'plannings' => $plannings,
            'dates'     => $dates,
            'stats'     => $stats,
        ]);
    }

    #[Route('/new', name: 'planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $planning = new Planningactivite();
        $form     = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->planningService->create($planning);
                $this->addFlash('success', 'Planning créé avec succès !');
                return $this->redirectToRoute('planning_index', [
                    'idActivite' => $planning->getActivite()->getIdActivite()
                ]);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('backOffice/admin/planning/new.html.twig', [
            'form' => $form,
        ]);
    }

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
            try {
                $this->planningService->update($planning);
                $this->addFlash('success', 'Planning modifié avec succès !');
                return $this->redirectToRoute('planning_index', [
                    'idActivite' => $planning->getActivite()->getIdActivite()
                ]);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('backOffice/admin/planning/edit.html.twig', [
            'form'     => $form,
            'planning' => $planning,
        ]);
    }

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
            $this->addFlash('success', 'Planning supprimé.');
        } else {
            $this->addFlash('error', 'Token de sécurité invalide.');
        }

        return $this->redirectToRoute('planning_index', ['idActivite' => $idActivite]);
    }

    #[Route('/{id}/reserver', name: 'planning_reserver', methods: ['POST'])]
    public function reserver(
        int $id,
        Request $request,
        ReservationActiviteService $reservationService
    ): Response {
        $planning = $this->planningService->findById($id);

        if (!$planning) {
            throw $this->createNotFoundException("Planning #$id introuvable");
        }

        $idActivite = $planning->getActivite()->getIdActivite();

        if (!$this->isCsrfTokenValid('reserver_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('activite_show_front', ['id' => $idActivite]);
        }

        // ✅ Cast |null pour éviter le "if always true"
        /** @var \App\Entity\Users|null $utilisateur */
        $utilisateur = $this->getUser();

        if ($utilisateur) {
            $result = $reservationService->reserverPourUtilisateur($id, $utilisateur);
        } else {
            $nom   = trim($request->request->get('nom_client', ''));
            $email = trim($request->request->get('email_client', ''));
            if (empty($nom) || empty($email)) {
                $this->addFlash('error', 'Veuillez renseigner votre nom et email.');
                return $this->redirectToRoute('activite_show_front', ['id' => $idActivite]);
            }
            $result = $reservationService->reserverAnonyme($id, $nom, $email);
        }

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('activite_show_front', ['id' => $idActivite]);
    }

    #[Route('/{id}/annuler', name: 'planning_annuler', methods: ['POST'])]
    public function annuler(int $id, Request $request): Response
    {
        $planning = $this->planningService->findById($id);

        if (!$planning) {
            throw $this->createNotFoundException("Planning #$id introuvable");
        }

        $idActivite = $planning->getActivite()->getIdActivite();

        if (!$this->isCsrfTokenValid('annuler_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('activite_show_front', ['id' => $idActivite]);
        }

        $result = $this->planningService->annulerReservation($id);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('activite_show_front', ['id' => $idActivite]);
    }
}