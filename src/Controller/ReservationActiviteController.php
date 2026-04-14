<?php

namespace App\Controller;

use App\Service\ReservationActiviteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reservations-activite')]
class ReservationActiviteController extends AbstractController
{
    public function __construct(
        private ReservationActiviteService $reservationService,
    ) {}

    // ── Réserver (utilisateur connecté OU anonyme) ─────────────────────────

    #[Route('/reserver/{idPlanning}', name: 'reservation_activite_reserver', methods: ['POST'])]
    public function reserver(int $idPlanning, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('reserver_' . $idPlanning, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('activite_index_front');
        }

        $utilisateur = $this->getUser(); // null si non connecté

        if ($utilisateur) {
            // ── Utilisateur connecté ──
            $result = $this->reservationService->reserverPourUtilisateur($idPlanning, $utilisateur);
        } else {
            // ── Anonyme : récupérer nom + email depuis le formulaire ──
            $nom   = trim($request->request->get('nom_client', ''));
            $email = trim($request->request->get('email_client', ''));

            if (empty($nom) || empty($email)) {
                $this->addFlash('error', 'Veuillez renseigner votre nom et email.');
                // Récupérer l'id activité depuis le planning pour rediriger
                return $this->redirectToRoute('activite_index_front');
            }

            $result = $this->reservationService->reserverAnonyme($idPlanning, $nom, $email);
        }

        // Flash message selon le résultat
        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        // Rediriger vers la page de l'activité
        $idActivite = $result['reservation']?->getPlanning()->getActivite()->getIdActivite()
                   ?? $request->request->get('id_activite');

        return $this->redirectToRoute('activite_show_front', ['id' => $idActivite]);
    }

    // ── Annuler une réservation ────────────────────────────────────────────

    #[Route('/annuler/{id}', name: 'reservation_activite_annuler', methods: ['POST'])]
    public function annuler(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('annuler_reservation_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('mes_reservations_activite');
        }

        $utilisateur = $this->getUser();
        $result      = $this->reservationService->annuler($id, $utilisateur);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('mes_reservations_activite');
    }

    // ── Mes réservations (utilisateur connecté) ────────────────────────────

    #[Route('/mes-reservations', name: 'mes_reservations_activite', methods: ['GET'])]
    public function mesReservations(): Response
    {
        $utilisateur = $this->getUser();

        if (!$utilisateur) {
            $this->addFlash('error', 'Connectez-vous pour voir vos réservations.');
            return $this->redirectToRoute('activite_index_front');
        }

        $reservations = $this->reservationService->findByUtilisateur($utilisateur);

        return $this->render('frontOffice/mes_reservation.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    // ── Liste admin (toutes les réservations d'une activité) ──────────────

    #[Route('/admin/activite/{idActivite}', name: 'admin_reservations_activite', methods: ['GET'])]
    public function adminIndex(int $idActivite): Response
    {
        $reservations = $this->reservationService->findByActivite($idActivite);

        return $this->render('backOffice/admin/reservation_activite/index.html.twig', [
            'reservations' => $reservations,
            'idActivite'   => $idActivite,
        ]);
    }
}
