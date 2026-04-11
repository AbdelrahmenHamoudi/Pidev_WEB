<?php

namespace App\Controller\Hebergement;

use App\Entity\Hebergement;
use App\Entity\Reservation;
use App\Form\HebergementType;
use App\Repository\HebergementRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class BackendController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('backend/admin/dashboard.html.twig');
    }

    #[Route('/admin/login', name: 'app_admin_login')]
    public function login(): Response
    {
        return $this->render('backend/admin/login.html.twig');
    }

    #[Route('/admin/profile', name: 'app_admin_profile')]
    public function profile(): Response
    {
        return $this->render('backend/admin/profile.html.twig');
    }

    #[Route('/admin/profile/edit', name: 'app_admin_profile_edit')]
    public function profileEdit(Request $request): Response
    {
        // Pour l'instant, on retourne simplement le template
        // Plus tard, on pourra ajouter la logique de modification
        return $this->render('backend/admin/profile_edit.html.twig');
    }

    // ==================== HEBERGEMENT CRUD ====================

    #[Route('/admin/hebergements', name: 'app_admin_hebergement_list')]
    public function hebergementList(Request $request, HebergementRepository $repository): Response
    {
        $titre = $request->query->get('titre');
        $sort = $request->query->get('sort', 'default');

        $qb = $repository->createQueryBuilder('h');

        if ($titre) {
            $qb->andWhere('h.titre LIKE :titre')
               ->setParameter('titre', '%' . $titre . '%');
        }

        if ($sort === 'price_asc') {
            $qb->orderBy('h.prixParNuit', 'ASC');
        } elseif ($sort === 'price_desc') {
            $qb->orderBy('h.prixParNuit', 'DESC');
        } else {
            $qb->orderBy('h.id_hebergement', 'DESC');
        }

        $hebergements = $qb->getQuery()->getResult();

        return $this->render('backend/admin/hebergement/list.html.twig', [
            'hebergements' => $hebergements,
            'current_titre' => $titre,
            'current_sort' => $sort
        ]);
    }

    #[Route('/admin/hebergements/new', name: 'app_admin_hebergement_new')]
    public function hebergementNew(
        Request $request, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger
    ): Response {
        $hebergement = new Hebergement();
        $form = $this->createForm(HebergementType::class, $hebergement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle multiple images - simplified approach
            /** @var UploadedFile[] $uploadedFiles */
            $uploadedFiles = $form->get('imageFiles')->getData();
            $uploadCount = 0;
            
            if ($uploadedFiles) {
                foreach ($uploadedFiles as $index => $file) {
                    if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                    
                    try {
                        $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/hebergements';
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0777, true);
                        }
                        
                        $file->move($targetDir, $newFilename);
                        
                        $imagePath = 'uploads/hebergements/' . $newFilename;
                        $hebergement->addImage($imagePath);
                        $uploadCount++;
                        
                        if ($uploadCount === 1) {
                            $hebergement->setImage($imagePath);
                        }
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur upload: ' . $e->getMessage());
                    }
                }
            }
            
            // Set a default placeholder if no image was uploaded
            if ($uploadCount === 0) {
                $hebergement->setImage('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800');
            }
            
            $em->persist($hebergement);
            $em->flush();

            $this->addFlash('success', 'Hébergement créé avec succès (' . $uploadCount . ' image(s))');
            return $this->redirectToRoute('app_admin_hebergement_list');
        }

        return $this->render('backend/admin/hebergement/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/hebergements/{id}/edit', name: 'app_admin_hebergement_edit')]
    public function hebergementEdit(
        Request $request,
        Hebergement $hebergement,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(HebergementType::class, $hebergement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle new images - simplified approach
            /** @var UploadedFile[] $uploadedFiles */
            $uploadedFiles = $form->get('imageFiles')->getData();
            
            if ($uploadedFiles) {
                $uploadCount = 0;
                foreach ($uploadedFiles as $index => $file) {
                    if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                    
                    try {
                        $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/hebergements';
                        $file->move($targetDir, $newFilename);
                        
                        $imagePath = 'uploads/hebergements/' . $newFilename;
                        $hebergement->addImage($imagePath);
                        $uploadCount++;
                        
                        // If it's the first file uploaded during this edit, make it the primary image
                        if ($uploadCount === 1) {
                            $hebergement->setImage($imagePath);
                        }
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur upload: ' . $file->getClientOriginalName());
                    }
                }
            }

            $em->flush();

            $this->addFlash('success', 'Hébergement modifié avec succès');
            return $this->redirectToRoute('app_admin_hebergement_list');
        }

        return $this->render('backend/admin/hebergement/edit.html.twig', [
            'form' => $form->createView(),
            'hebergement' => $hebergement
        ]);
    }

    #[Route('/admin/hebergements/{id}/delete', name: 'app_admin_hebergement_delete', methods: ['POST'])]
    public function hebergementDelete(
        Request $request,
        Hebergement $hebergement,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $hebergement->getIdHebergement(), $request->request->get('_token'))) {
            $em->remove($hebergement);
            $em->flush();
            $this->addFlash('success', 'Hébergement supprimé avec succès');
        }

        return $this->redirectToRoute('app_admin_hebergement_list');
    }

    #[Route('/admin/hebergements/{id}', name: 'app_admin_hebergement_show')]
    public function hebergementShow(Hebergement $hebergement): Response
    {
        return $this->render('backend/admin/hebergement/show.html.twig', [
            'hebergement' => $hebergement
        ]);
    }

    // ==================== RESERVATION MANAGEMENT ====================

    #[Route('/admin/reservations', name: 'app_admin_reservation_list')]
    public function reservationList(EntityManagerInterface $em): Response
    {
        $reservations = $em->createQuery(
            'SELECT r, h FROM App\Entity\Reservation r LEFT JOIN r.hebergement_id h ORDER BY r.dateDebutR DESC'
        )->getResult();
        
        return $this->render('backend/admin/reservation/list.html.twig', [
            'reservations' => $reservations
        ]);
    }

    #[Route('/admin/reservations/{id}/confirm', name: 'app_admin_reservation_confirm', methods: ['POST'])]
    public function confirmReservation(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $em
    ): Response {
        // Prevent multiple confirmations
        if ($reservation->getStatutR() === 'CONFIRMEE') {
            $this->addFlash('warning', 'Cette réservation est déjà confirmée.');
            return $this->redirectToRoute('app_admin_reservation_list');
        }

        if ($this->isCsrfTokenValid('confirm' . $reservation->getIdReservation(), $request->request->get('_token'))) {
            $reservation->setStatutR('CONFIRMEE');
            $em->flush();
            $this->addFlash('success', 'Réservation #' . $reservation->getIdReservation() . ' confirmée avec succès');
        }

        return $this->redirectToRoute('app_admin_reservation_list');
    }

    #[Route('/admin/reservations/{id}/reject', name: 'app_admin_reservation_reject', methods: ['POST'])]
    public function rejectReservation(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $em
    ): Response {
        // Prevent action on already processed reservation
        if (in_array($reservation->getStatutR(), ['REFUSEE', 'ANNULEE', 'CONFIRMEE'])) {
            $this->addFlash('warning', 'Cette réservation ne peut plus être rejetée.');
            return $this->redirectToRoute('app_admin_reservation_list');
        }

        if ($this->isCsrfTokenValid('reject' . $reservation->getIdReservation(), $request->request->get('_token'))) {
            $reservation->setStatutR('REFUSEE');
            $em->flush();
            $this->addFlash('success', 'Réservation #' . $reservation->getIdReservation() . ' refusée');
        }

        return $this->redirectToRoute('app_admin_reservation_list');
    }

    #[Route('/admin/reservations/{id}/cancel', name: 'app_admin_reservation_cancel', methods: ['POST'])]
    public function cancelReservation(
        Request $request,
        Reservation $reservation,
        EntityManagerInterface $em
    ): Response {
        // Prevent cancellation of already cancelled reservation
        if ($reservation->getStatutR() === 'ANNULEE') {
            $this->addFlash('warning', 'Cette réservation est déjà annulée.');
            return $this->redirectToRoute('app_admin_reservation_list');
        }

        if ($this->isCsrfTokenValid('cancel' . $reservation->getIdReservation(), $request->request->get('_token'))) {
            $id = $reservation->getIdReservation();
            $em->remove($reservation);
            $em->flush();
            $this->addFlash('success', 'Réservation #' . $id . ' supprimée avec succès');
        }

        return $this->redirectToRoute('app_admin_reservation_list');
    }
}
