<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\HebergementRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class FrontendController extends AbstractController
{
    private const STATIC_USER_ID = 32; // iheb user id from SQL

    #[Route('/', name: 'app_home')]
    public function index(HebergementRepository $hebergementRepository): Response
    {
        // Get available hebergements to display on homepage
        $hebergements = $hebergementRepository->findAvailable();
        
        return $this->render('frontend/index.html.twig', [
            'hebergements' => $hebergements
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, SessionInterface $session): Response
    {
        // Set static user session for demo
        $session->set('user_id', self::STATIC_USER_ID);
        $session->set('user_email', 'iheb@gmail.com');
        $session->set('user_name', 'iheb hmidi');
        
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('frontend/login.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername,
        ]);
    }

    #[route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(): Response
    {
        return $this->render('frontend/forgot_password.html.twig');
    }

    // ==================== HEBERGEMENTS (VIEW ONLY FOR FRONT) ====================

    #[Route('/hebergements', name: 'app_hebergements')]
    public function hebergements(HebergementRepository $repository): Response
    {
        $hebergements = $repository->findAvailable();
        return $this->render('frontend/hebergement/list.html.twig', [
            'hebergements' => $hebergements
        ]);
    }

    #[Route('/hebergements/{id}', name: 'app_hebergement_detail')]
    public function hebergementDetail($id, HebergementRepository $repository): Response
    {
        $hebergement = $repository->find($id);
        
        if (!$hebergement || !$hebergement->isDisponible()) {
            throw $this->createNotFoundException('Hébergement non disponible');
        }
        
        return $this->render('frontend/hebergement/detail.html.twig', [
            'hebergement' => $hebergement
        ]);
    }

    // ==================== RESERVATION CRUD ====================

    #[Route('/mes-reservations', name: 'app_reservations_list')]
    public function reservationsList(ReservationRepository $repository, SessionInterface $session): Response
    {
        $userId = $session->get('user_id', self::STATIC_USER_ID);
        $reservations = $repository->findByUser($userId);
        
        return $this->render('frontend/reservation/list.html.twig', [
            'reservations' => $reservations
        ]);
    }

    #[Route('/reservation/new/{hebergementId}', name: 'app_reservation_new')]
    public function reservationNew(
        Request $request,
        $hebergementId,
        HebergementRepository $hebergementRepository,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        $hebergement = $hebergementRepository->find($hebergementId);
        
        if (!$hebergement || !$hebergement->isDisponible()) {
            $this->addFlash('error', 'Cet hébergement n\'est pas disponible');
            return $this->redirectToRoute('app_hebergements');
        }

        $reservation = new Reservation();
        $reservation->setHebergement($hebergement);
        $reservation->setUserId($session->get('user_id', self::STATIC_USER_ID));
        
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setStatut('EN ATTENTE');
            
            $em->persist($reservation);
            $em->flush();

            $this->addFlash('success', 'Votre réservation a été créée avec succès');
            return $this->redirectToRoute('app_reservations_list');
        }

        return $this->render('frontend/reservation/new.html.twig', [
            'form' => $form->createView(),
            'hebergement' => $hebergement
        ]);
    }

    #[Route('/reservation/{id}/edit', name: 'app_reservation_edit')]
    public function reservationEdit(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        // Check ownership
        if ($reservation->getUserId() !== $session->get('user_id', self::STATIC_USER_ID)) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette réservation');
        }

        // Only allow editing if status is pending
        if (!in_array($reservation->getStatut(), ['EN ATTENTE', 'PENDING'])) {
            $this->addFlash('error', 'Vous ne pouvez modifier que les réservations en attente');
            return $this->redirectToRoute('app_reservations_list');
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Réservation modifiée avec succès');
            return $this->redirectToRoute('app_reservations_list');
        }

        return $this->render('frontend/reservation/edit.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation
        ]);
    }

    #[Route('/reservation/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST'])]
    public function reservationCancel(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        // Check ownership
        if ($reservation->getUserId() !== $session->get('user_id', self::STATIC_USER_ID)) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        if ($this->isCsrfTokenValid('cancel' . $reservation->getId(), $request->request->get('_token'))) {
            $reservation->setStatut('Annulée');
            $em->flush();
            $this->addFlash('success', 'Réservation annulée avec succès');
        }

        return $this->redirectToRoute('app_reservations_list');
    }

    #[Route('/reservation/{id}/delete', name: 'app_reservation_delete', methods: ['POST'])]
    public function reservationDelete(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        // Check ownership
        if ($reservation->getUserId() !== $session->get('user_id', self::STATIC_USER_ID)) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        // Only allow deletion if cancelled or pending
        if (!in_array($reservation->getStatut(), ['Annulée', 'EN ATTENTE', 'PENDING'])) {
            $this->addFlash('error', 'Cette réservation ne peut pas être supprimée');
            return $this->redirectToRoute('app_reservations_list');
        }

        if ($this->isCsrfTokenValid('delete' . $reservation->getId(), $request->request->get('_token'))) {
            $em->remove($reservation);
            $em->flush();
            $this->addFlash('success', 'Réservation supprimée avec succès');
        }

        return $this->redirectToRoute('app_reservations_list');
    }

    #[Route('/reservation/{id}', name: 'app_reservation_show')]
    public function reservationShow(Reservation $reservation, SessionInterface $session): Response
    {
        // Check ownership
        if ($reservation->getUserId() !== $session->get('user_id', self::STATIC_USER_ID)) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        return $this->render('frontend/reservation/show.html.twig', [
            'reservation' => $reservation
        ]);
    }
}
