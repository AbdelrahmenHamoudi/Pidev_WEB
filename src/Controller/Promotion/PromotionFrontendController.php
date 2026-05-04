<?php

namespace App\Controller\Promotion;

use App\Entity\Activite;
use App\Entity\Hebergement;
use App\Entity\Promotion;
use App\Entity\ReservationPromo;
use App\Entity\Voiture;
use App\Repository\PromotionRepository;
use App\Repository\ReservationPromoRepository;
use App\Service\PriceCalculatorService;
use App\Service\ReservationPromoService;
use App\Service\TrendingService;
use App\Service\PromoCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/promotions')]
final class PromotionFrontendController extends AbstractController
{
    public function __construct(
        private ReservationPromoService $reservationService,
        private ReservationPromoRepository $reservationRepo,
        private PriceCalculatorService $priceCalc,
        private EntityManagerInterface $em,
        private TrendingService $trendingService,
        private PromoCodeService $promoCodeService,
        private \App\Service\SmartDiscountService $smartDiscountService

    ) {}

    /**
     * List all promotions with filters and search
     */
    #[Route('', name: 'app_promotion_list')]
    public function list(Request $request, PromotionRepository $repo): Response
    {
        // 🧠 Automatic Smart Discount Engine Execution
        try {
            $this->smartDiscountService->runEngine();
        } catch (\Exception $e) {
            // Silently fail if engine has issues (e.g. database locked)
        }

        $filters = [
            'nom' => $request->query->get('nom', ''),
            'promo_type' => $request->query->get('promo_type', ''),
            'statut' => $request->query->get('statut', ''),
            'offer_type' => $request->query->get('offer_type', ''),
            'sort_by' => $request->query->get('sort_by', 'date'),
            'tendance' => $request->query->get('tendance', ''),
        ];

        // Build query with filters
        $qb = $repo->createQueryBuilder('p');
        
        if (!empty($filters['nom'])) {
            $qb->andWhere('p.name LIKE :nom OR p.description LIKE :nom')
               ->setParameter('nom', '%' . $filters['nom'] . '%');
        }
        
        if (!empty($filters['promo_type'])) {
            if ($filters['promo_type'] === 'pack') {
                $qb->andWhere('p.promoType = :promoType')
                ->setParameter('promoType', Promotion::TYPE_PACK);
            } else {
                $qb->andWhere('p.promoType = :promoType')
                ->setParameter('promoType', Promotion::TYPE_INDIVIDUELLE);
            }
        }

        // Sorting
        switch ($filters['sort_by']) {
            case 'nom':
                $qb->orderBy('p.name', 'ASC');
                break;
            case 'reduction':
                $qb->orderBy('p.discountPercentage', 'DESC');
                break;
            case 'date':
            default:
                $qb->orderBy('p.startDate', 'DESC');
                break;
        }

        $promotions = $qb->getQuery()->getResult();
         //  Filter by trending if requested
        if (!empty($filters['tendance'])) {
            $promotions = array_filter($promotions, fn($p) => $this->trendingService->isTrending($p));
        }

        // Always sort trending promos first, then apply the selected sort
        usort($promotions, function($a, $b) use ($filters) {
            $ta = $this->trendingService->isTrending($a) ? 0 : 1;
            $tb = $this->trendingService->isTrending($b) ? 0 : 1;
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }

            switch ($filters['sort_by']) {
                case 'nom':
                    return $a->getName() <=> $b->getName();
                case 'reduction':
                    return ($b->getDiscountPercentage() ?? 0) <=> ($a->getDiscountPercentage() ?? 0);
                case 'tendance':
                    return $this->trendingService->getTrendingScore($b) <=> $this->trendingService->getTrendingScore($a);
                case 'date':
                default:
                    return $b->getStartDate() <=> $a->getStartDate();
            }
        });

        // Transform entities to data arrays for the template/JavaScript
        $promotionsData = [];
        $offerDetails = []; // 
        $packDetails = [];  // 
        
        /** @var Promotion $promo */
        foreach ($promotions as $promo) {
            $status = $promo->getStatus(); // active, upcoming, expired
            $statusColors = $promo->getStatusColors();
            
            // Filter by status (post-query for complex date logic if needed)
            if (!empty($filters['statut']) && $filters['statut'] !== $status) {
                continue;
            }
            
            // Filter by offer type (for individual promos)
            if (!empty($filters['offer_type']) && !$promo->isPack()) {
                $refType = $promo->getOfferType(); // hebergement, voiture, activite
                if ($refType !== $filters['offer_type']) {
                    continue;
                }
            }

             // ADD: Check if locked and get promo code info
        $isVerrouille = $promo->isVerrouille();
        $promoCodeData = null;
        if ($isVerrouille) {
            $code = $this->promoCodeService->getActiveCode($promo->getId());
            if ($code) {
                $promoCodeData = [
                    'code' => $code->getCode(),
                    'qr_url' => $this->promoCodeService->generateQrImageUrl($code->getQrContent())
                ];
            }
        }
            // ADD: Check trending status (AJOUTE CES 3 LIGNES AVANT LE TABLEAU $data)
            $isTrending = $this->trendingService->isTrending($promo);
            $trendingScore = $this->trendingService->getTrendingScore($promo);
            $trendingLabel = $this->trendingService->getScoreLabel($promo);
            $data = [
                'id'           => $promo->getId(),
                'name'         => $promo->getName(),
                'description'  => $promo->getDescription(),
                'image'        => $promo->getImage(),
                'isPack'       => $promo->isPack(),
                'status'       => $status,
                'statusLabel'  => $promo->getStatusLabel(),
                'statusBg'     => $statusColors['bg'],
                'statusColor'  => $statusColors['color'],
                'isReservable' => $promo->isReservable(),
                'startDate'    => $promo->getStartDate()->format('Y-m-d'),
                'endDate'      => $promo->getEndDate()->format('Y-m-d'),
                'reductionLabel' => $promo->getReductionLabel(),
                'discountPct'  => $promo->getDiscountPercentage(),
                'discountFixed'=> $promo->getDiscountFixed(),
                'promoType'    => $promo->isPack() ? 'pack' : 'individuelle',
                'offerType'    => $promo->isPack() ? null : $promo->getOfferType(),    // ← FIXED
                'referenceId'  => $promo->isPack() ? null : $promo->getReferenceId(),
                'isLocked'     => $isVerrouille,
                'promoCode'    => $promoCodeData,
                'isTrending'   => $isTrending,
                'trendingScore'=> $trendingScore,
                'trendingLabel'=> $trendingLabel,
            ];
            
            $promotionsData[$promo->getId()] = $data;

            // Prepare offer details for JS modal
            if ($promo->isPack()) {
                $items = [];
                foreach ($promo->getPackItemsDecoded() as $item) {
                    $entity = null;
                    $unitPrice = 0;
                    $maxPersons = 99;
                    
                    if ($item['type'] === 'hebergement') {
                        $entity = $this->em->getRepository(Hebergement::class)->find($item['id']);
                        $unitPrice = $entity ? (float)$entity->getPrixParNuit() : 0;
                    } elseif ($item['type'] === 'voiture') {
                        $entity = $this->em->getRepository(Voiture::class)->find($item['id']);
                        $unitPrice = $entity ? (float)$entity->getPrix_km() : 0;
                        $maxPersons = $entity ? $entity->getNb_places() : 4;
                    } elseif ($item['type'] === 'activite') {
                        $entity = $this->em->getRepository(Activite::class)->find($item['id']);
                        $unitPrice = $entity ? (float)$entity->getPrixParPersonne() : 0;
                        $maxPersons = $entity ? $entity->getCapaciteMax() : 99;
                    }
                    
                    if ($entity) {
                        $items[] = [
                            'id' => $item['id'],
                            'type' => $item['type'],
                            'label' => $item['label'] ?? ($entity instanceof Hebergement ? $entity->getTitre() : ($entity instanceof Voiture ? $entity->getMarque() . ' ' . $entity->getModele() : $entity->getNomA())),
                            'unitPrice' => $unitPrice,
                            'maxPersons' => $maxPersons,
                        ];
                    }
                }
                $packDetails[$promo->getId()] = $items;
            } else {
                // Individual offer details
            $refId = $promo->getReferenceId();
            $refType = $promo->getOfferType();
            $offerData = ['unitPrice' => 0, 'label' => '', 'maxPersons' => 99];
                
                if ($refType === 'hebergement' && $refId) {
                    $h = $this->em->getRepository(Hebergement::class)->find($refId);
                    if ($h) {
                        $offerData = [
                            'unitPrice' => (float)$h->getPrixParNuit(),
                            'label' => $h->getTitre(),
                            'maxPersons' => $h->getCapacite() ?? 4,
                        ];
                    }
                } elseif ($refType === 'voiture' && $refId) {
                    $v = $this->em->getRepository(Voiture::class)->find($refId);
                    if ($v) {
                        $offerData = [
                            'unitPrice' => (float)$v->getPrix_km(),
                            'label' => $v->getMarque() . ' ' . $v->getModele(),
                            'maxPersons' => $v->getNb_places(),
                        ];
                    }
                } elseif ($refType === 'activite' && $refId) {
                    $a = $this->em->getRepository(Activite::class)->find($refId);
                    if ($a) {
                        $offerData = [
                            'unitPrice' => (float)$a->getPrixParPersonne(),
                            'label' => $a->getNomA(),
                            'maxPersons' => $a->getCapaciteMax(),
                        ];
                    }
                }
                $offerDetails[$promo->getId()] = $offerData;
            }
        }
        $trendingCount = $this->trendingService->countTrending($promotions);

        // Smart Scheduler / Expiration alert check
        $expiringPromos = [];
        $now = new \DateTime();
        foreach ($promotions as $promo) {
            $hoursToExpiration = ($promo->getEndDate()->getTimestamp() - $now->getTimestamp()) / 3600;
            if ($hoursToExpiration > 0 && $hoursToExpiration <= 24) {
                $expiringPromos[] = [
                    'id' => $promo->getId(),
                    'name' => $promo->getName(),
                ];
            }
        }

        return $this->render('frontend/promotion/list.html.twig', [
            'promotionsData' => $promotionsData,
            'offerJsonStr' => json_encode($offerDetails),
            'packJsonStr' => json_encode($packDetails),
            'expiringPromos' => json_encode($expiringPromos),
            'filters' => $filters,
            'trendingCount' => $trendingCount,  
        ]);
    }

    /**
     * AJAX: Increment views count
     */
    #[Route('/{id}/increment-view', name: 'promo_increment_view', methods: ['POST'])]
    public function incrementView(Promotion $promotion): JsonResponse
    {
        $promotion->incrementViews();
        $this->em->flush();
        
        return $this->json([
            'success' => true,
            'views' => $promotion->getViews()
        ]);
    }

    /**
     * AJAX: Calculate price for modal preview
     */
    #[Route('/prix/ajax', name: 'promo_prix_ajax', methods: ['POST'])]
    public function prixAjax(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? null;
        $id = (int)($data['id'] ?? 0);
        $promotionId = (int)($data['promotion_id'] ?? 0);

        try {
            $promotion = $this->em->getRepository(Promotion::class)->find($promotionId);
            if (!$promotion) {
                return $this->json(['success' => false, 'error' => 'Promotion non trouvée'], 404);
            }

            $priceData = match($type) {
                'hebergement' => $this->priceCalc->calculateHebergementPrice(
                    $this->em->getRepository(Hebergement::class)->find($id),
                    new \DateTime($data['date_debut']),
                    new \DateTime($data['date_fin']),
                    $promotion
                ),
                'voiture' => $this->priceCalc->calculateVoiturePrice(
                    $this->em->getRepository(Voiture::class)->find($id),
                    (float)$data['distance_km'],
                    $promotion
                ),
                'activite' => $this->priceCalc->calculateActivitePrice(
                    $this->em->getRepository(Activite::class)->find($id),
                    (int)$data['nb_personnes'],
                    $promotion
                ),
                default => throw new \InvalidArgumentException('Type inconnu: ' . $type)
            };

            return $this->json(['success' => true, 'data' => $priceData]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * AJAX: Create reservation from modal
     */
    #[Route('/reserver/ajax', name: 'promo_reserver_ajax', methods: ['POST'])]
    public function reserverAjax(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Users) {
            return $this->json(['success' => false, 'error' => 'Vous devez être connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $promotionId = (int)($data['promotion_id'] ?? 0);

        try {
            $promotion = $this->em->getRepository(Promotion::class)->find($promotionId);
            if (!$promotion) {
                return $this->json(['success' => false, 'error' => 'Promotion non trouvée'], 404);
            }

            if ($promotion->isVerrouille()) {
                $code = $data['promo_code'] ?? '';
                if (empty($code)) {
                    return $this->json(['success' => false, 'error' => 'Code promo requis'], 400);
                }

                $validation = $this->promoCodeService->validateCode($code, $promotionId);
                if (!$validation['valid']) {
                    return $this->json(['success' => false, 'error' => $validation['message']], 400);
                }

                // Mark code as used
                $this->promoCodeService->markCodeAsUsed($code, $user->getId());
            }
            // Check if pack reservation
            if (!empty($data['pack_inputs']) && is_array($data['pack_inputs'])) {
                $resa = $this->reservationService->createPackReservation($user, $promotion, $data['pack_inputs']);
            } else {
                // Individual reservation based on promotion type
                $offerType = $promotion->getOfferType();
                
                $resa = match($offerType) {
                    'hebergement' => $this->reservationService->createHebergementReservation(
                        $user, 
                        $promotion,
                        $this->em->getRepository(Hebergement::class)->find($promotion->getReferenceId()),
                        $data['date_debut'],
                        $data['date_fin']
                    ),
                    'voiture' => $this->reservationService->createVoitureReservation(
                        $user,
                        $promotion,
                        $this->em->getRepository(Voiture::class)->find($promotion->getReferenceId()),
                        (float)($data['distance_km'] ?? 0),
                        $data['point_depart'] ?? 'Non spécifié',
                        $data['point_arrivee'] ?? 'Non spécifié',
                        $data['date_reservation'] ?? date('Y-m-d'),
                        (int)($data['nb_personnes'] ?? 1)
                    ),
                    'activite' => $this->reservationService->createActiviteReservation(
                        $user,
                        $promotion,
                        $this->em->getRepository(Activite::class)->find($promotion->getReferenceId()),
                        (int)($data['nb_personnes'] ?? 1),
                        $data['date_activite'] ?? date('Y-m-d')
                    ),
                    default => throw new \InvalidArgumentException('Type d\'offre non supporté')
                };
            }

            return $this->json([
                'success' => true, 
                'message' => '✅ Réservation créée avec succès !',
                'reservation_id' => $resa->getId()
            ]);

        } catch (\InvalidArgumentException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => 'Erreur lors de la réservation: ' . $e->getMessage()], 500);
        }
    }

    //-----------------------------------------------------------------------------------------
    //Validate Promo Code AJAX
// ════════════════════════════════════════════════════════════════════════

    #[Route('/validate-code/ajax', name: 'promo_validate_code_ajax', methods: ['POST'])]
    public function validateCodeAjax(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';
        $promotionId = (int)($data['promotion_id'] ?? 0);

        $result = $this->promoCodeService->validateCode($code, $promotionId);

        return $this->json($result);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  Mark code as used when reserving
    // ════════════════════════════════════════════════════════════════════════

    #[Route('/use-code/ajax', name: 'promo_use_code_ajax', methods: ['POST'])]
    public function useCodeAjax(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Users) {
            return $this->json(['success' => false, 'error' => 'Non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';

        $success = $this->promoCodeService->markCodeAsUsed($code, $user->getId());

        return $this->json([
            'success' => $success,
            'message' => $success ? 'Code marqué comme utilisé' : 'Échec'
        ]);
    }

    /**
     * "Mes Réservations" - List current user's promo reservations
     */
    #[Route('/mes-reservations', name: 'promo_mes_reservations')]
    public function mesReservations(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Users) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos réservations.');
        }

        $reservations = $this->reservationRepo->findByUser($user->getId());
        
        // Enrich with offer details (hébergement/voiture/activite) for display
        $enriched = [];
        foreach ($reservations as $resa) {
            $offerDetail = null;
            
            try {
                switch ($resa->getOfferType()) {
                    case 'hebergement':
                        $offerDetail = $resa->getHebergementId() 
                            ? $this->em->getRepository(Hebergement::class)->find($resa->getHebergementId())
                            : null;
                        break;
                    case 'voiture':
                        $offerDetail = $resa->getVoitureId()
                            ? $this->em->getRepository(Voiture::class)->find($resa->getVoitureId())
                            : null;
                        break;
                    case 'activite':
                        $offerDetail = $resa->getActiviteId()
                            ? $this->em->getRepository(Activite::class)->find($resa->getActiviteId())
                            : null;
                        break;
                }
            } catch (\Exception $e) {
                // Offer may have been deleted
            }
            
            $enriched[] = [
                'resa' => $resa,
                'offerDetail' => $offerDetail,
            ];
        }

        return $this->render('frontend/promotion/mes_reservations.html.twig', [
            'enriched' => $enriched,
        ]);
    }

    /**
     * Detail view of a specific reservation
     */
    #[Route('/mes-reservations/{id}', name: 'promo_reservation_detail', requirements: ['id' => '\d+'])]
    public function detail(ReservationPromo $resa): Response
    {
        if ($resa->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $offerDetail = null;
        switch ($resa->getOfferType()) {
            case 'hebergement':
                $offerDetail = $this->em->getRepository(Hebergement::class)->find($resa->getHebergementId());
                break;
            case 'voiture':
                $offerDetail = $this->em->getRepository(Voiture::class)->find($resa->getVoitureId());
                break;
            case 'activite':
                $offerDetail = $this->em->getRepository(Activite::class)->find($resa->getActiviteId());
                break;
        }

        return $this->render('frontend/promotion/reservation_detail.html.twig', [
            'resa' => $resa,
            'offerDetail' => $offerDetail,
        ]);
    }

    /**
     * AJAX endpoint: Update reservation (24h editable window)
     */
    #[Route('/mes-reservations/{id}/modifier', name: 'promo_reservation_modifier', methods: ['POST'])]
    public function modifier(Request $request, ReservationPromo $resa): JsonResponse
    {
        if ($resa->getUser() !== $this->getUser()) {
            return $this->json(['success' => false, 'error' => 'Accès refusé.'], 403);
        }

        if (!$resa->isEditable()) {
            return $this->json([
                'success' => false, 
                'error' => 'Cette réservation ne peut plus être modifiée (délai de 24h dépassé).'
            ], 400);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['success' => false, 'error' => 'Données JSON invalides.'], 400);
        }

        try {
            $this->reservationService->updateReservation($resa, $data);
            
            return $this->json([
                'success' => true,
                'message' => '✅ Réservation mise à jour avec succès.'
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (\LogicException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => 'Une erreur est survenue.'], 500);
        }
    }

    /**
     * Delete reservation (24h editable window)
     */
    #[Route('/mes-reservations/{id}/supprimer', name: 'promo_reservation_supprimer', methods: ['POST'])]
    public function supprimer(Request $request, ReservationPromo $resa): Response
    {
        if ($resa->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_resa_' . $resa->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('promo_mes_reservations');
        }

        try {
            $this->reservationService->deleteReservation($resa);
            $this->addFlash('success', 'La réservation a été supprimée avec succès.');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression.');
        }

        return $this->redirectToRoute('promo_mes_reservations');
    }
}