<?php

namespace App\Controller\Hebergement;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\HebergementRepository;
use App\Repository\ReservationRepository;
use App\Repository\UsersRepository;
<<<<<<< Updated upstream
=======
use App\Service\Hebergement\SafeZoneService;
use App\Service\Hebergement\TravelSimulationService;
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream

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

    // ==================== HEBERGEMENTS (UNDER /RESERVATION) ====================
=======
>>>>>>> Stashed changes

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

    // ==================== HEBERGEMENTS (UNDER /RESERVATION) ====================
    
    #[Route('/reservation/details/{id}', name: 'app_hebergement_detail')]
    public function hebergementDetail($id, Request $request, HebergementRepository $repository, SafeZoneService $safeZoneService, EntityManagerInterface $em): Response
    {
        $hebergement = $repository->find($id);
        
        if (!$hebergement) {
            throw $this->createNotFoundException('Hébergement introuvable');
        }

        // Fetch safety data dynamically (no DB changes)
        $safetyData = $safeZoneService->getSafetyData($hebergement);

        // --- NEW EMBEDDED RESERVATION FORM LOGIC ---
        $reservation = new Reservation();
        $reservation->setHebergement($hebergement);
        $user = $this->getUser();
        if ($user) {
            $reservation->setUser($user);
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$user) {
                $this->addFlash('error', 'flash.login_required');
                // Could redirect back to self or login, going to login
                return $this->redirectToRoute('app_login');
            }

            if ($form->isValid()) {
                $reservation->setStatutR('CONFIRMÉE');
                $em->persist($reservation);
                $em->flush();

                $this->addFlash('success', 'flash.reservation_created');
                return $this->redirectToRoute('app_reservations_list');
            }
        }
        // -------------------------------------------
        
        return $this->render('frontend/hebergement/detail.html.twig', [
            'hebergement' => $hebergement,
            'safetyData' => $safetyData,
            'form' => $form->createView()
        ]);
    }

    #[Route('/hebergement/simulate/{id}', name: 'app_hebergement_simulate', methods: ['POST'])]
    public function simulateTravel(
        int $id,
        Request $request,
        HebergementRepository $repository,
        TravelSimulationService $simulationService
    ): Response {
        $hebergement = $repository->find($id);
        if (!$hebergement) {
            return $this->json(['error' => 'Hébergement introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        $preferences = [
            'profile' => $data['profile'] ?? 'Aventure',
            'budget' => $data['budget'] ?? 'Moyen',
            'noise' => $data['noise'] ?? 'faible',
            'crowd' => $data['crowd'] ?? 'moyen',
        ];

        $simulation = $simulationService->generateSimulation($hebergement, $preferences);

        return $this->json($simulation);
    }

    // ==================== RESERVATION CRUD ====================

    #[Route('/reservation/mes-voyages', name: 'app_reservations_list')]
    public function reservationsList(
        Request $request,
        ReservationRepository $repository
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'flash.login_required');
            return $this->redirectToRoute('app_login');
        }

        $titre = $request->query->get('titre');
        $type = $request->query->get('type', 'all');
        $capacite = $request->query->get('capacite');

        // Build query for personal reservations with filters
        $qb = $repository->createQueryBuilder('r')
            ->join('r.hebergement_id', 'h')
            ->where('r.user = :user')
            ->setParameter('user', $user);

        if ($titre) {
            $qb->andWhere('h.titre LIKE :titre')
               ->setParameter('titre', '%' . $titre . '%');
        }

        if ($type && $type !== 'all') {
            $qb->andWhere('h.type_hebergement = :type')
               ->setParameter('type', $type);
        }

        if ($capacite) {
            $qb->andWhere('h.capacite >= :capacite')
               ->setParameter('capacite', $capacite);
        }

        $reservations = $qb->getQuery()->getResult();

        return $this->render('frontend/reservation/list.html.twig', [
            'reservations' => $reservations,
            'current_titre' => $titre,
            'current_type' => $type,
            'current_capacite' => $capacite,
        ]);
    }

<<<<<<< Updated upstream
    #[Route('/reservation/reserver/{hebergementId}', name: 'app_reservation_new')]
    public function reservationNew(
        Request $request,
        $hebergementId,
        HebergementRepository $hebergementRepository,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour réserver un hébergement.');
            return $this->redirectToRoute('app_login');
        }

        $hebergement = $hebergementRepository->find($hebergementId);
        
        if (!$hebergement || !$hebergement->isDisponibleHeberg()) {
            $this->addFlash('error', 'Cet hébergement n\'est pas disponible');
            return $this->redirectToRoute('app_home');
        }

        $reservation = new Reservation();
        $reservation->setHebergement($hebergement);
        $reservation->setDateDebutR(new \DateTime());
        $reservation->setDateFinR((new \DateTime())->modify('+1 day'));
        $reservation->setUser($user);
        
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setStatutR('EN ATTENTE');
            
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
=======
    // ReservationNew method removed since the form is now embedded in the detail page.
>>>>>>> Stashed changes

    #[Route('/reservation/{id}/edit', name: 'app_reservation_edit')]
    public function reservationEdit(
        Request $request,
        Reservation $reservation,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        // Check ownership
        if (!$user || $reservation->getUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette réservation');
        }

        // Only allow editing if status is pending
        if (!in_array($reservation->getStatutR(), ['EN ATTENTE', 'PENDING'])) {
<<<<<<< Updated upstream
            $this->addFlash('error', 'Vous ne pouvez modifier que les réservations en attente');
=======
            $this->addFlash('error', 'flash.edit_not_allowed');
>>>>>>> Stashed changes
            return $this->redirectToRoute('app_reservations_list');
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
<<<<<<< Updated upstream
            $this->addFlash('success', 'Réservation modifiée avec succès');
=======
            $this->addFlash('success', 'flash.reservation_updated');
>>>>>>> Stashed changes
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
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        // Check ownership
        if (!$user || $reservation->getUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        // Prevent multiple cancellations or cancelling confirmed ones
        if ($reservation->getStatutR() === 'Annulée' || $reservation->getStatutR() === 'ANNULEE') {
            $this->addFlash('warning', 'Cette réservation est déjà annulée.');
            return $this->redirectToRoute('app_reservations_list');
        }

        if ($this->isCsrfTokenValid('cancel' . $reservation->getIdReservation(), $request->request->get('_token'))) {
            $reservation->setStatutR('Annulée');
            $em->flush();
<<<<<<< Updated upstream
            $this->addFlash('success', 'Réservation annulée avec succès');
=======
            $this->addFlash('success', 'flash.reservation_cancelled');
>>>>>>> Stashed changes
        }

        return $this->redirectToRoute('app_reservations_list');
    }

    #[Route('/reservation/{id}/delete', name: 'app_reservation_delete', methods: ['POST'])]
    public function reservationDelete(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        // Check ownership
        if (!$user || $reservation->getUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        // Only allow deletion if cancelled or pending
        if (!in_array($reservation->getStatutR(), ['Annulée', 'EN ATTENTE', 'PENDING'])) {
            $this->addFlash('error', 'Cette réservation ne peut pas être supprimée');
            return $this->redirectToRoute('app_reservations_list');
        }

        if ($this->isCsrfTokenValid('delete' . $reservation->getIdReservation(), $request->request->get('_token'))) {
            $em->remove($reservation);
            $em->flush();
<<<<<<< Updated upstream
            $this->addFlash('success', 'Réservation supprimée avec succès');
=======
            $this->addFlash('success', 'flash.reservation_deleted');
>>>>>>> Stashed changes
        }

        return $this->redirectToRoute('app_reservations_list');
    }

    #[Route('/reservation/recherche', name: 'app_hebergements_search')]
    public function searchReservation(Request $request, HebergementRepository $repository): Response
    {
        $type = $request->query->get('type', 'all');
        $titre = $request->query->get('titre');
        $capacite = $request->query->get('capacite');
        $disponible = $request->query->get('disponible');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $sort = $request->query->get('sort', 'default');
        
        // Build criteria array
        $criteria = [];
        
        if ($type && $type !== 'all') {
            $criteria['type_hebergement'] = $type;
        }
        
        if ($disponible === '1') {
            $criteria['disponible_heberg'] = true;
        } elseif ($disponible === '0') {
            $criteria['disponible_heberg'] = false;
        }
        
        // Get results based on criteria
        if (!empty($criteria)) {
            $hebergements = $repository->findBy($criteria);
        } else {
            $hebergements = $repository->findAll();
        }
        
        // Filter by titre (partial match)
        if ($titre) {
            $hebergements = array_filter($hebergements, function($h) use ($titre) {
                return stripos($h->getTitre(), $titre) !== false;
            });
        }
        
        // Filter by capacite (minimum)
        if ($capacite && is_numeric($capacite)) {
            $hebergements = array_filter($hebergements, function($h) use ($capacite) {
                return $h->getCapacite() >= (int)$capacite;
            });
        }
        
<<<<<<< Updated upstream
=======
        // Filter by price range
        if ($minPrice !== null && is_numeric($minPrice)) {
            $hebergements = array_filter($hebergements, function($h) use ($minPrice) {
                return $h->getPrixParNuit() >= (float)$minPrice;
            });
        }
        if ($maxPrice !== null && is_numeric($maxPrice)) {
            $hebergements = array_filter($hebergements, function($h) use ($maxPrice) {
                return $h->getPrixParNuit() <= (float)$maxPrice;
            });
        }
        
>>>>>>> Stashed changes
        // Sort by price
        if ($sort === 'price_asc') {
            usort($hebergements, function($a, $b) {
                return $a->getPrixParNuit() <=> $b->getPrixParNuit();
            });
        } elseif ($sort === 'price_desc') {
            usort($hebergements, function($a, $b) {
                return $b->getPrixParNuit() <=> $a->getPrixParNuit();
            });
        }
        
        // Reset array keys
        $hebergements = array_values($hebergements);
        
        $template = $request->isXmlHttpRequest() || $request->query->get('ajax')
            ? 'frontend/reservation/_search_results.html.twig'
            : 'frontend/reservation/search.html.twig';

        return $this->render($template, [
            'hebergements' => $hebergements,
            'current_type' => $type,
            'current_titre' => $titre,
            'current_capacite' => $capacite,
            'current_disponible' => $disponible,
            'current_min_price' => $minPrice,
            'current_max_price' => $maxPrice,
            'current_sort' => $sort
        ]);
    }

    #[Route('/reservation/{id}', name: 'app_reservation_show')]
    public function reservationShow(Reservation $reservation): Response
    {
        /** @var \App\Entity\Users|null $user */
        $user = $this->getUser();
        // Check ownership
        if (!$user || $reservation->getUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        return $this->render('frontend/reservation/show.html.twig', [
            'reservation' => $reservation
        ]);
    }
}
