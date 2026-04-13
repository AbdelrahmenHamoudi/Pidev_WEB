<?php

namespace App\Controller\Hebergement;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\HebergementRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontendController extends AbstractController
{
    private const STATIC_USER_ID = 32;

    #[Route('/', name: 'app_home')]
    public function index(HebergementRepository $hebergementRepository): Response
    {
        $hebergements = $hebergementRepository->findAvailable();
        
        return $this->render('frontend/index.html.twig', [
            'hebergements' => $hebergements
        ]);
    }

    // ==================== HEBERGEMENTS ====================

    #[Route('/reservation/details/{id}', name: 'app_hebergement_detail')]
    public function hebergementDetail($id, HebergementRepository $repository): Response
    {
        $hebergement = $repository->find($id);
        
        if (!$hebergement || !$hebergement->isDisponibleHeberg()) {
            throw $this->createNotFoundException('Hébergement non disponible');
        }
        
        return $this->render('frontend/hebergement/detail.html.twig', [
            'hebergement' => $hebergement
        ]);
    }

    // ==================== RESERVATION CRUD ====================

    #[Route('/reservation/mes-voyages', name: 'app_reservations_list')]
    public function reservationsList(
        Request $request,
        ReservationRepository $repository
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à vos voyages.');
            return $this->redirectToRoute('app_login');
        }

        $titre = $request->query->get('titre');
        $type = $request->query->get('type', 'all');
        $capacite = $request->query->get('capacite');

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

    #[Route('/reservation/reserver/{hebergementId}', name: 'app_reservation_new')]
    public function reservationNew(
        Request $request,
        $hebergementId,
        HebergementRepository $hebergementRepository,
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

    #[Route('/reservation/{id}/edit', name: 'app_reservation_edit')]
    public function reservationEdit(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        if (!$user || $reservation->getUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé');
        }

        if (!in_array($reservation->getStatutR(), ['EN ATTENTE', 'PENDING'])) {
            $this->addFlash('error', 'Modification non autorisée');
            return $this->redirectToRoute('app_reservations_list');
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Réservation modifiée');
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

        if (!$user || $reservation->getUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        if ($this->isCsrfTokenValid('cancel' . $reservation->getIdReservation(), $request->request->get('_token'))) {
            $reservation->setStatutR('Annulée');
            $em->flush();
            $this->addFlash('success', 'Réservation annulée');
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

        if (!$user || $reservation->getUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        if ($this->isCsrfTokenValid('delete' . $reservation->getIdReservation(), $request->request->get('_token'))) {
            $em->remove($reservation);
            $em->flush();
            $this->addFlash('success', 'Réservation supprimée');
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
        $sort = $request->query->get('sort', 'default');
        
        $criteria = [];
        
        if ($type && $type !== 'all') {
            $criteria['type_hebergement'] = $type;
        }
        
        if ($disponible === '1') {
            $criteria['disponible_heberg'] = true;
        } elseif ($disponible === '0') {
            $criteria['disponible_heberg'] = false;
        }
        
        if (!empty($criteria)) {
            $hebergements = $repository->findBy($criteria);
        } else {
            $hebergements = $repository->findAll();
        }
        
        if ($titre) {
            $hebergements = array_filter($hebergements, function($h) use ($titre) {
                return stripos($h->getTitre(), $titre) !== false;
            });
        }
        
        if ($capacite && is_numeric($capacite)) {
            $hebergements = array_filter($hebergements, function($h) use ($capacite) {
                return $h->getCapacite() >= (int)$capacite;
            });
        }
        
        if ($sort === 'price_asc') {
            usort($hebergements, function($a, $b) {
                return $a->getPrixParNuit() <=> $b->getPrixParNuit();
            });
        } elseif ($sort === 'price_desc') {
            usort($hebergements, function($a, $b) {
                return $b->getPrixParNuit() <=> $a->getPrixParNuit();
            });
        }
        
        $hebergements = array_values($hebergements);
        
        return $this->render('frontend/reservation/search.html.twig', [
            'hebergements' => $hebergements,
            'current_type' => $type,
            'current_titre' => $titre,
            'current_capacite' => $capacite,
            'current_disponible' => $disponible,
            'current_sort' => $sort
        ]);
    }

    #[Route('/reservation/{id}', name: 'app_reservation_show')]
    public function reservationShow(Reservation $reservation): Response
    {
        $user = $this->getUser();

        if (!$user || $reservation->getUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        return $this->render('frontend/reservation/show.html.twig', [
            'reservation' => $reservation
        ]);
    }
}