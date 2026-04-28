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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use App\Service\Hebergement\GroqService;
use App\Service\Hebergement\SafeZoneService;
use App\Service\Hebergement\DynamicPricingService;

final class BackendController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(HebergementRepository $hRepo, ReservationRepository $rRepo): Response
    {
        return $this->render('backend/admin/dashboard.html.twig', [
            'totalH' => count($hRepo->findAll()),
            'totalR' => count($rRepo->findAll()),
            'pendingR' => count($rRepo->findBy(['statutR' => ['EN ATTENTE', 'PENDING']])),
        ]);
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
            // Set a temporary placeholder so flush doesn't fail due to NOT NULL constraint
            $hebergement->setImage('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800');
            
            // First persist to get the ID
            $em->persist($hebergement);
            $em->flush();
            $id = $hebergement->getId();

            /** @var UploadedFile[] $uploadedFiles */
            $uploadedFiles = $form->get('imageFiles')->getData();
            $mainImagePath = null;
            
            if ($uploadedFiles) {
                $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/hebergements/' . $id;
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                foreach ($uploadedFiles as $file) {
                    if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) continue;
                    
                    $newFilename = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) 
                                   . '-' . uniqid() . '.' . $file->guessExtension();
                    
                    try {
                        $file->move($targetDir, $newFilename);
                        if (!$mainImagePath) {
                            $mainImagePath = 'uploads/hebergements/' . $id . '/' . $newFilename;
                        }
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur upload: ' . $e->getMessage());
                    }
                }
            }
            
            // Update with the real first image if any were uploaded
            if ($mainImagePath) {
                $hebergement->setImage($mainImagePath);
                $em->flush();
            }

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
            /** @var UploadedFile[] $uploadedFiles */
            $uploadedFiles = $form->get('imageFiles')->getData();
            
            if ($uploadedFiles) {
                $id = $hebergement->getId();
                $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/hebergements/' . $id;
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                foreach ($uploadedFiles as $file) {
                    if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) continue;
                    
                    $newFilename = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) 
                                   . '-' . uniqid() . '.' . $file->guessExtension();
                    
                    try {
                        $file->move($targetDir, $newFilename);
                        // Optional: update main image if it was empty or placeholder
                        if (str_contains($hebergement->getImage(), 'unsplash') || empty($hebergement->getImage())) {
                            $hebergement->setImage('uploads/hebergements/' . $id . '/' . $newFilename);
                        }
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur upload: ' . $file->getClientOriginalName());
                    }
                }
            }

            $em->flush();

            return $this->redirectToRoute('app_admin_hebergement_list');
        }

        return $this->render('backend/admin/hebergement/edit.html.twig', [
            'form' => $form->createView(),
            'hebergement' => $hebergement
        ]);
    }

    #[Route('/admin/hebergements/{id}/image/delete', name: 'app_admin_hebergement_delete_image')]
    public function deleteImage(
        Hebergement $hebergement,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $filename = $request->query->get('filename');
        if (!$filename) {
            return $this->redirectToRoute('app_admin_hebergement_edit', ['id' => $hebergement->getId()]);
        }

        // Security: avoid directory traversal and ensure it's in the correct folder
        $safeFilename = basename($filename);
        $projectRoot = str_replace('\\', '/', $this->getParameter('kernel.project_dir'));
        $filePath = $projectRoot . '/public/uploads/hebergements/' . $hebergement->getId() . '/' . $safeFilename;

        if (file_exists($filePath)) {
            unlink($filePath);
            
            // If the deleted image was the primary one in DB, update it with another one from the same folder
            $dbPath = 'uploads/hebergements/' . $hebergement->getId() . '/' . $safeFilename;
            if ($hebergement->getImage() === $dbPath) {
                // We refresh images list from folder
                $remainingImages = $hebergement->getImages();
                if (!empty($remainingImages)) {
                    $hebergement->setImage($remainingImages[0]->getFilename());
                } else {
                    $hebergement->setImage('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800');
                }
                $em->flush();
            }
        }

        return $this->redirectToRoute('app_admin_hebergement_edit', ['id' => $hebergement->getId()]);
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

    #[Route('/admin/api/hebergement/{id}/dynamic-pricing', name: 'api_admin_hebergement_dynamic_pricing')]
    public function adminHebergementDynamicPricingApi(
        Hebergement $hebergement,
        DynamicPricingService $pricingService
    ): Response {
        $pricing = $pricingService->getDynamicPricing($hebergement);

        return $this->json($pricing);
    }

    #[Route('/admin/intelligence/pricing', name: 'app_admin_pricing_dashboard')]
    public function adminPricingDashboard(
        EntityManagerInterface $em,
        DynamicPricingService $pricingService
    ): Response {
        $hebergements = $em->getRepository(Hebergement::class)->findAll();
        $globalStats = $pricingService->getGlobalMarketDashboard($hebergements);

        // Prepare simple listing data
        $pricingList = [];
        foreach ($hebergements as $h) {
            $pricingList[] = [
                'hebergement' => $h,
                'pricing' => $pricingService->getDynamicPricing($h)
            ];
        }

        return $this->render('backend/admin/pricing/dashboard.html.twig', [
            'stats' => $globalStats,
            'pricingList' => $pricingList
        ]);
    }

    #[Route('/admin/api/pricing/toggle/{id}', name: 'api_admin_pricing_toggle', methods: ['POST'])]
    public function togglePricing(
        int $id,
        EntityManagerInterface $em,
        DynamicPricingService $pricingService
    ): Response {
        $hebergement = $em->getRepository(Hebergement::class)->find($id);
        if (!$hebergement) {
            return $this->json(['success' => false, 'error' => 'Not found'], 404);
        }

        $newState = $pricingService->toggleEnabled($id);
        $newPrice = null;

        if ($newState) {
            // If turning ON, apply the recommended price immediately
            $pricingData = $pricingService->getDynamicPricing($hebergement);
            $newPrice = $pricingData['recommendedPrice'];
            $hebergement->setPrixParNuit((float) $newPrice);
            $em->flush();
        }

        return $this->json([
            'success' => true, 
            'enabled' => $newState,
            'newPrice' => $newPrice
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
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        // Prevent cancellation of already cancelled reservation
        if ($reservation->getStatutR() === 'ANNULEE') {
            $this->addFlash('warning', 'Cette réservation est déjà annulée.');
            return $this->redirectToRoute('app_admin_reservation_list');
        }

        if ($this->isCsrfTokenValid('cancel' . $reservation->getIdReservation(), $request->request->get('_token'))) {
            $id = $reservation->getIdReservation();
            
            // Get user information before deletion
            $user = $reservation->getUser();
            $email = $user ? $user->getE_mail() : null;
            $userName = $user ? ($user->getPrenom() . ' ' . $user->getNom()) : 'Client';
            
            // Get hebergement title
            $hebergement = $reservation->getHebergement();
            $hebergementTitle = $hebergement ? $hebergement->getTitre() : 'votre hébergement';

            if ($email) {
                try {
                    $templEmail = (new TemplatedEmail())
                        ->from(new Address('hmidiiheb393@gmail.com', 'Re7la - Gestion des Réservations'))
                        ->to($email)
                        ->subject('Information importante concernant votre réservation #' . $id)
                        ->htmlTemplate('emails/reservation_cancellation.html.twig')
                        ->context([
                            'userName' => $userName,
                            'hebergementTitle' => $hebergementTitle,
                            'dateDebut' => $reservation->getDateDebut(),
                            'dateFin' => $reservation->getDateFin(),
                        ]);

                    $mailer->send($templEmail);
                } catch (\Exception $e) {
                    // Log error but continue deletion process
                    $this->addFlash('error', 'Erreur d\'envoi : ' . $e->getMessage());
                }
            }

            $em->remove($reservation);
            $em->flush();
            $this->addFlash('success', 'Réservation supprimée avec succès et notification envoyée à ' . ($email ?? 'l\'utilisateur'));
        }

        return $this->redirectToRoute('app_admin_reservation_list');
    }
}