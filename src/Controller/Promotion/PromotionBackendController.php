<?php

namespace App\Controller\Promotion;

use App\Entity\Promotion;
use App\Form\PromotionType;
use App\Repository\PromotionRepository;
use App\Repository\HebergementRepository;
use App\Repository\VoitureRepository;
use App\Repository\ActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\PromoCodeService;
use App\Service\TrendingService;
use App\Service\PusherService;
use App\Service\Api2PdfService;
use Symfony\Component\HttpFoundation\JsonResponse;

final class PromotionBackendController extends AbstractController
{
    // ════════════════════════════════════════════
    // LISTE
    // ════════════════════════════════════════════

    #[Route('/admin/promotion', name: 'app_admin_promotion_list')]
    public function list(Request $request, PromotionRepository $repo, TrendingService $trendingService): Response
    {
        $q = $request->query->get('q');
        $type = $request->query->get('type');
        $offerType = $request->query->get('offerType');
        $status    = $request->query->get('status');
        $tendance  = $request->query->getBoolean('tendance', false);

        if ($q || $type || $offerType || $status || $tendance) {
            $promotions = $repo->search($q, $type, $offerType, $status, $tendance);
        } else {
            $promotions = $repo->findAll();
        }

        $allPromos    = $repo->findAll();
        $today        = new \DateTime('today');
        $total        = count($allPromos);
        $totalPacks   = count(array_filter($allPromos, fn($p) => $p->isPack()));
        $totalIndiv   = $total - $totalPacks;
        $totalActives = count(array_filter($allPromos,
            fn($p) => $p->getStartDate() && $p->getEndDate()
                   && $p->getStartDate() <= $today && $p->getEndDate() >= $today));
        $totalTrending = $trendingService->countTrending($allPromos);

        return $this->render('backend/admin/promotion/list.html.twig', [
            'promotions'     => $promotions,
            'total'          => $total,
            'totalPacks'     => $totalPacks,
            'totalIndiv'     => $totalIndiv,
            'totalActives'   => $totalActives,
            'totalTrending'  => $totalTrending,
            'trendingService'=> $trendingService,
            'q'              => $q,
            'currentType'    => $type,
            'currentOffer'   => $offerType,
            'currentStatus'  => $status
        ]);
    }

    // ════════════════════════════════════════════
    // DÉTAILS
    // ════════════════════════════════════════════

    #[Route('/admin/promotion/{id}/show', name: 'app_admin_promotion_show', requirements: ['id' => '\d+'])]
    public function show(
        Promotion $promotion,
        EntityManagerInterface $em,
        TrendingService $trendingService,
        PromoCodeService $promoCodeService
    ): Response {
        $offerDetail = null;

        if (!$promotion->isPack() && $promotion->getOfferType() && $promotion->getReferenceId()) {
            $offerDetail = match ($promotion->getOfferType()) {
                'hebergement' => $em->getRepository(\App\Entity\Hebergement::class)->find($promotion->getReferenceId()),
                'activite'    => $em->getRepository(\App\Entity\Activite::class)->find($promotion->getReferenceId()),
                'voiture'     => $em->getRepository(\App\Entity\Voiture::class)->find($promotion->getReferenceId()),
                default       => null,
            };
        }

        $nbReservations = $trendingService->getNbReservations($promotion);
        $nbVues         = $trendingService->getNbVues($promotion);
        $isTrending     = $trendingService->isTrending($promotion);

        // Get promo code if locked
        $promoCode = null;
        $qrUrl     = null;
        if ($promotion->isVerrouille()) {
            $promoCode = $promoCodeService->getActiveCode($promotion->getId());
            if ($promoCode) {
                $qrUrl = $promoCodeService->generateQrImageUrl($promoCode->getQrContent());
            }
        }

        return $this->render('backend/admin/promotion/show.html.twig', [
            'promotion'      => $promotion,
            'offerDetail'    => $offerDetail,
            'nbReservations' => $nbReservations,
            'nbVues'         => $nbVues,
            'isTrending'     => $isTrending,
            'promoCode'      => $promoCode,
            'qrUrl'          => $qrUrl,
        ]);
    }

    // ════════════════════════════════════════════
    // EXPORT PDF
    // ════════════════════════════════════════════

    #[Route('/admin/promotion/{id}/pdf', name: 'app_admin_promotion_pdf', requirements: ['id' => '\d+'])]
    public function exportPdf(
        Promotion $promotion,
        EntityManagerInterface $em,
        TrendingService $trendingService,
        PromoCodeService $promoCodeService,
        Api2PdfService $api2PdfService
    ): Response {
        $offerDetail = null;

        if (!$promotion->isPack() && $promotion->getOfferType() && $promotion->getReferenceId()) {
            $offerDetail = match ($promotion->getOfferType()) {
                'hebergement' => $em->getRepository(\App\Entity\Hebergement::class)->find($promotion->getReferenceId()),
                'activite'    => $em->getRepository(\App\Entity\Activite::class)->find($promotion->getReferenceId()),
                'voiture'     => $em->getRepository(\App\Entity\Voiture::class)->find($promotion->getReferenceId()),
                default       => null,
            };
        }

        $nbReservations = $trendingService->getNbReservations($promotion);
        $nbVues         = $trendingService->getNbVues($promotion);
        $isTrending     = $trendingService->isTrending($promotion);

        $promoCode = null;
        $qrUrl     = null;
        if ($promotion->isVerrouille()) {
            $promoCode = $promoCodeService->getActiveCode($promotion->getId());
            if ($promoCode) {
                $qrUrl = $promoCodeService->generateQrImageUrl($promoCode->getQrContent());
            }
        }

        $html = $this->renderView('backend/admin/promotion/pdf.html.twig', [
            'promotion'      => $promotion,
            'offerDetail'    => $offerDetail,
            'nbReservations' => $nbReservations,
            'nbVues'         => $nbVues,
            'isTrending'     => $isTrending,
            'promoCode'      => $promoCode,
            'qrUrl'          => $qrUrl,
        ]);

        // ── Use Api2Pdf REST API instead of local Dompdf ──
        $filename = 'promotion_' . $promotion->getId() . '.pdf';
        $result   = $api2PdfService->htmlToPdf($html, $filename);

        if ($result['success'] && $result['pdf_url']) {
            // Download the generated PDF and return it as a file response
            $pdfContent = $api2PdfService->downloadPdf($result['pdf_url']);
            if ($pdfContent) {
                return new Response($pdfContent, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
            }
        }

        // Fallback: if Api2Pdf fails, show error
        $this->addFlash('error', 'Erreur lors de la génération du PDF: ' . ($result['error'] ?? 'Erreur inconnue'));
        return $this->redirectToRoute('app_admin_promotion_show', ['id' => $promotion->getId()]);
    }

    // ════════════════════════════════════════════
    // NOUVELLE PROMOTION
    // ════════════════════════════════════════════

    #[Route('/new', name: 'app_admin_promotion_new', methods: ['GET', 'POST'])]
    public function adminNew(
        Request               $request,
        SluggerInterface      $slugger,
        HebergementRepository $hebergementRepository,
        ActiviteRepository    $activiteRepository,
        VoitureRepository     $voitureRepository,
        EntityManagerInterface $em,
        PromoCodeService      $promoCodeService,
        PusherService         $pusherService
    ): Response {
        $promotion = new Promotion();
        $form      = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $offerType   = trim((string) $request->request->get('offer_type', ''));
            $referenceId = (int) $request->request->get('reference_id', 0);
            $packItems   = trim((string) $request->request->get('pack_items_json', '[]'));
            $isVerrouille = $form->get('isVerrouille')->getData();
            $promotion->setIsVerrouille($isVerrouille ? true : false);

            if ($promotion->isPack()) {
                $decoded = json_decode($packItems, true);
                if (!is_array($decoded) || count($decoded) < 2) {
                    $this->addFlash('error', 'Un pack doit contenir au moins 2 offres.');
                    return $this->render('backend/admin/promotion/new.html.twig', [
                        'form'         => $form,
                        'promotion'    => $promotion,
                        'hebergements' => $hebergementRepository->findAll(),
                        'activites'    => $activiteRepository->findAll(),
                        'voitures'     => $voitureRepository->findAll(),
                    ]);
                }
                $promotion->setPackItems($packItems);
                $promotion->setOfferType(null);
                $promotion->setReferenceId(null);
            } else {
                if (!$offerType || !$referenceId) {
                    $this->addFlash('error', 'Veuillez sélectionner une offre (hébergement, voiture ou activité).');
                    return $this->render('backend/admin/promotion/new.html.twig', [
                        'form'         => $form,
                        'promotion'    => $promotion,
                        'hebergements' => $hebergementRepository->findAll(),
                        'activites'    => $activiteRepository->findAll(),
                        'voitures'     => $voitureRepository->findAll(),
                    ]);
                }
                $promotion->setOfferType($offerType);
                $promotion->setReferenceId($referenceId);
                $promotion->setPackItems(null);
            }

            /** @var UploadedFile|null $imageFile */
            $imageFile = $request->files->get('promo_image');
            if (!$imageFile && $form->has('imageFile')) {
                $imageFile = $form->get('imageFile')->getData();
            }

            if ($imageFile instanceof UploadedFile) {
                $imagePath = $this->uploadImageSafe($imageFile, $slugger);
                if ($imagePath) {
                    $promotion->setImage($imagePath);
                } else {
                    $this->addFlash('error', 'Image refusée: extension non valide.');
                }
            }

            $em->persist($promotion);
            $em->flush();

            // Generate promo code AFTER flush (we need the ID)
            if ($isVerrouille) {
                $promoCodeService->createPromoCode($promotion->getId());
                $this->addFlash('success', '✅ Promotion verrouillée créée avec code promo généré automatiquement.');
            } else {
                $this->addFlash('success', '✅ Promotion créée avec succès.');
            }

            // ── Pusher: broadcast real-time notification to frontend ──
            try {
                $pusherService->trigger('promotions', 'new-promo', [
                    'id'       => $promotion->getId(),
                    'name'     => $promotion->getName(),
                    'discount' => $promotion->getReductionLabel(),
                    'message'  => '🎉 Nouvelle promotion: ' . $promotion->getName(),
                ]);
            } catch (\Exception $e) {
                // Pusher is optional — don't block on failure
            }

            return $this->redirectToRoute('app_admin_promotion_list');
        }

        return $this->render('backend/admin/promotion/new.html.twig', [
            'form'         => $form,
            'promotion'    => $promotion,
            'hebergements' => $hebergementRepository->findAll(),
            'activites'    => $activiteRepository->findAll(),
            'voitures'     => $voitureRepository->findAll(),
        ]);
    }

    // ════════════════════════════════════════════
    // QR CODE GENERATION ENDPOINT
    // ════════════════════════════════════════════

    #[Route('/admin/promotion/{id}/generate-qr', name: 'app_admin_promotion_generate_qr', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function generateQr(
        Promotion $promotion,
        PromoCodeService $promoCodeService,
        EntityManagerInterface $em
    ): JsonResponse {
        $promoCode = $promoCodeService->createPromoCode($promotion->getId());
        $qrUrl     = $promoCodeService->generateQrImageUrl($promoCode->getQrContent());

        return $this->json([
            'success' => true,
            'code'    => $promoCode->getCode(),
            'qr_url'  => $qrUrl,
            'message' => '✅ Code promo généré avec succès!'
        ]);
    }

    // ════════════════════════════════════════════
    // MODIFIER
    // ════════════════════════════════════════════

    #[Route('/admin/promotion/{id}/edit', name: 'app_admin_promotion_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        Promotion $promotion,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        PromoCodeService $promoCodeService,
        PusherService $pusherService
    ): Response {
        $form = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        $hebergements = $em->getRepository(\App\Entity\Hebergement::class)->findAll();
        $activites    = $em->getRepository(\App\Entity\Activite::class)->findAll();
        $voitures     = $em->getRepository(\App\Entity\Voiture::class)->findAll();

        if ($form->isSubmitted() && $form->isValid()) {

            if ($promotion->getDiscountPercentage() === null && $promotion->getDiscountFixed() === null) {
                $this->addFlash('error', 'Renseignez au moins un type de réduction.');
                goto renderEdit;
            }

            $promoType = $promotion->getPromoType();

            if ($promoType === 'individuelle') {
                $offerType   = $request->request->get('offer_type');
                $referenceId = (int) $request->request->get('reference_id');
                if (!$offerType || !$referenceId) {
                    $this->addFlash('error', 'Sélectionnez une offre pour la promotion individuelle.');
                    goto renderEdit;
                }
                $promotion->setOfferType($offerType);
                $promotion->setReferenceId($referenceId);
                $promotion->setPackItems(null);
            } else {
                $packJson = $request->request->get('pack_items_json', '[]');
                $items    = json_decode($packJson, true) ?? [];
                if (count($items) < 2) {
                    $this->addFlash('error', 'Un pack doit contenir au moins 2 offres.');
                    goto renderEdit;
                }
                $promotion->setPackItems($packJson);
                $promotion->setOfferType(null);
                $promotion->setReferenceId(null);
            }

            $isVerrouille = $form->get('isVerrouille')->getData();
            $wasLocked = $promotion->isVerrouille();
            $promotion->setIsVerrouille($isVerrouille ? true : false);

            /** @var UploadedFile|null $imageFile */
            $imageFile = $request->files->get('promo_image');
            if (!$imageFile && $form->has('imageFile')) {
                $imageFile = $form->get('imageFile')->getData();
            }

            if ($imageFile instanceof UploadedFile) {
                $imagePath = $this->uploadImageSafe($imageFile, $slugger);
                if ($imagePath) {
                    $promotion->setImage($imagePath);
                } else {
                    $this->addFlash('error', 'Image refusée: extension non valide.');
                }
            }

            $em->flush();

            // Generate code only if newly locked (after flush so ID is available)
            if ($isVerrouille && !$wasLocked) {
                $promoCodeService->createPromoCode($promotion->getId());
                $this->addFlash('info', '🔒 Promotion verrouillée. Code promo généré automatiquement.');
            }

            // ── Pusher: broadcast real-time update notification ──
            try {
                $pusherService->trigger('promotions', 'promo-updated', [
                    'id'      => $promotion->getId(),
                    'name'    => $promotion->getName(),
                    'message' => '🔄 Promotion mise à jour: ' . $promotion->getName(),
                ]);
            } catch (\Exception $e) {
                // Pusher is optional — don't block on failure
            }

            $this->addFlash('success', 'Promotion modifiée avec succès !');
            return $this->redirectToRoute('app_admin_promotion_list');
        }

        renderEdit:
        $existingPackItems = '[]';
        if ($promotion->isPack() && $promotion->getPackItems()) {
            $existingPackItems = $promotion->getPackItems();
        }

        return $this->render('backend/admin/promotion/edit.html.twig', [
            'form'              => $form->createView(),
            'promotion'         => $promotion,
            'hebergements'      => $hebergements,
            'activites'         => $activites,
            'voitures'          => $voitures,
            'existingPromoType' => $promotion->getPromoType(),
            'existingOfferType' => $promotion->getOfferType() ?? '',
            'existingRefId'     => $promotion->getReferenceId() ?? 0,
            'existingPackItems' => $existingPackItems,
        ]);
    }

    // ════════════════════════════════════════════
    // SUPPRIMER
    // ════════════════════════════════════════════

    #[Route('/admin/promotion/{id}/delete', name: 'app_admin_promotion_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Request $request,
        Promotion $promotion,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $promotion->getId(), $request->request->get('_token'))) {
            $em->remove($promotion);
            $em->flush();
            $this->addFlash('success', 'Promotion supprimée avec succès.');
        }
        return $this->redirectToRoute('app_admin_promotion_list');
    }

    // ════════════════════════════════════════════
    // HELPER UPLOAD
    // ════════════════════════════════════════════

    private function uploadImageSafe(UploadedFile $file, SluggerInterface $slugger): ?string
    {
        $originalName = $file->getClientOriginalName();
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed      = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($extension, $allowed)) {
            return null;
        }

        $safeFilename = $slugger->slug(pathinfo($originalName, PATHINFO_FILENAME));
        $newFilename  = $safeFilename . '-' . uniqid() . '.' . $extension;
        $targetDir    = $this->getParameter('kernel.project_dir') . '/public/uploads/promotions';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        try {
            $file->move($targetDir, $newFilename);
            return 'uploads/promotions/' . $newFilename;
        } catch (\Exception $e) {
            return null;
        }
    }
}
