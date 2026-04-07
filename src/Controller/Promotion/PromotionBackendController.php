<?php

namespace App\Controller\Promotion;

use App\Entity\Promotion;
use App\Form\PromotionType;
use App\Repository\PromotionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class PromotionBackendController extends AbstractController
{
    // ════════════════════════════════════════════
    // LISTE
    // ════════════════════════════════════════════

    #[Route('/admin/promotion', name: 'app_admin_promotion_list')]
    public function list(PromotionRepository $repo): Response
    {
        $promotions   = $repo->findAll();
        $today        = new \DateTime('today');
        $total        = count($promotions);
        $totalPacks   = count(array_filter($promotions, fn($p) => $p->isPack()));
        $totalIndiv   = $total - $totalPacks;
        $totalActives = count(array_filter($promotions,
            fn($p) => $p->getStartDate() && $p->getEndDate()
                   && $p->getStartDate() <= $today && $p->getEndDate() >= $today));

        return $this->render('backend/admin/promotion/list.html.twig', [
            'promotions'   => $promotions,
            'total'        => $total,
            'totalPacks'   => $totalPacks,
            'totalIndiv'   => $totalIndiv,
            'totalActives' => $totalActives,
        ]);
    }

    // ════════════════════════════════════════════
    // DÉTAILS — NOUVEAU
    // ════════════════════════════════════════════
    //
    // Route ajoutée : /admin/promotion/{id}/show
    // Affiche toutes les informations d'une promotion :
    //   - Nom, description, période, type
    //   - Réduction, statut (Active / Bientôt / Expirée)
    //   - Offres incluses (individuelle ou pack)
    //
    #[Route('/admin/promotion/{id}/show', name: 'app_admin_promotion_show')]
    public function show(
        Promotion $promotion,
        EntityManagerInterface $em
    ): Response {
        // Pour les promos individuelles, charger l'offre ciblée depuis la BD
        $offerDetail = null;

        if (!$promotion->isPack() && $promotion->getOfferType() && $promotion->getReferenceId()) {
            $offerDetail = match ($promotion->getOfferType()) {
                'hebergement' => $em->getRepository(\App\Entity\Hebergement::class)->find($promotion->getReferenceId()),
                'activite'    => $em->getRepository(\App\Entity\Activite::class)->find($promotion->getReferenceId()),
                'voiture'     => $em->getRepository(\App\Entity\Voiture::class)->find($promotion->getReferenceId()),
                default       => null,
            };
        }

        return $this->render('backend/admin/promotion/show.html.twig', [
            'promotion'   => $promotion,
            'offerDetail' => $offerDetail,
        ]);
    }

    // ════════════════════════════════════════════
    // NOUVELLE PROMOTION
    // ════════════════════════════════════════════

    #[Route('/admin/promotion/new', name: 'app_admin_promotion_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $promotion    = new Promotion();
        $form         = $this->createForm(PromotionType::class, $promotion);
        $form->handleRequest($request);

        $hebergements = $em->getRepository(\App\Entity\Hebergement::class)->findAll();
        $activites    = $em->getRepository(\App\Entity\Activite::class)->findAll();
        $voitures     = $em->getRepository(\App\Entity\Voiture::class)->findAll();

        if ($form->isSubmitted() && $form->isValid()) {

            if ($promotion->getDiscountPercentage() === null && $promotion->getDiscountFixed() === null) {
                $this->addFlash('error', 'Renseignez au moins un type de réduction (% ou montant fixe).');
                goto renderNew;
            }

            $promoType = $promotion->getPromoType();

            if ($promoType === 'individuelle') {
                $offerType   = $request->request->get('offer_type');
                $referenceId = (int) $request->request->get('reference_id');
                if (!$offerType || !$referenceId) {
                    $this->addFlash('error', 'Sélectionnez une offre pour la promotion individuelle.');
                    goto renderNew;
                }
                $promotion->setOfferType($offerType);
                $promotion->setReferenceId($referenceId);
                $promotion->setPackItems(null);
            } else {
                $packJson = $request->request->get('pack_items_json', '[]');
                $items    = json_decode($packJson, true) ?? [];
                if (count($items) < 2) {
                    $this->addFlash('error', 'Un pack doit contenir au moins 2 offres. Actuellement : ' . count($items));
                    goto renderNew;
                }
                $promotion->setPackItems($packJson);
                $promotion->setOfferType(null);
                $promotion->setReferenceId(null);
            }

            /** @var UploadedFile|null $imageFile */
            $imageFile = $request->files->get('promo_image');
            if ($imageFile instanceof UploadedFile) {
                $imagePath = $this->uploadImageSafe($imageFile, $slugger);
                if ($imagePath) {
                    $promotion->setImage($imagePath);
                }
            }

            $em->persist($promotion);
            $em->flush();

            $this->addFlash('success', 'Promotion "' . $promotion->getName() . '" créée avec succès !');
            return $this->redirectToRoute('app_admin_promotion_list');
        }

        renderNew:
        return $this->render('backend/admin/promotion/new.html.twig', [
            'form'         => $form->createView(),
            'hebergements' => $hebergements,
            'activites'    => $activites,
            'voitures'     => $voitures,
        ]);
    }

    // ════════════════════════════════════════════
    // MODIFIER
    // ════════════════════════════════════════════

    #[Route('/admin/promotion/{id}/edit', name: 'app_admin_promotion_edit')]
    public function edit(
        Request $request,
        Promotion $promotion,
        EntityManagerInterface $em,
        SluggerInterface $slugger
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

            /** @var UploadedFile|null $imageFile */
            $imageFile = $request->files->get('promo_image');
            if ($imageFile instanceof UploadedFile) {
                $imagePath = $this->uploadImageSafe($imageFile, $slugger);
                if ($imagePath) {
                    $promotion->setImage($imagePath);
                }
            }

            $em->flush();
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

    #[Route('/admin/promotion/{id}/delete', name: 'app_admin_promotion_delete', methods: ['POST'])]
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
    // HELPER UPLOAD (sans guessExtension / fileinfo)
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

        $file->move($targetDir, $newFilename);
        return 'uploads/promotions/' . $newFilename;
    }
}