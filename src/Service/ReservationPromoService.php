<?php

namespace App\Service;

use App\Entity\Activite;
use App\Entity\Hebergement;
use App\Entity\Promotion;
use App\Entity\ReservationPromo;
use App\Entity\Users;
use App\Entity\Voiture;
use App\Repository\ReservationPromoRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * ReservationPromoService
 *
 * Gère toute la logique métier des réservations liées aux promotions :
 *   - Construction de la réservation depuis les données POST du modal
 *   - Validation des règles (dates, chevauchement, capacité…)
 *   - Sauvegarde en DB
 *   - Mise à jour et suppression (dans la fenêtre 24h)
 */
class ReservationPromoService
{
    public function __construct(
        private readonly EntityManagerInterface    $em,
        private readonly ReservationPromoRepository $repo,
        private readonly PriceCalculatorService   $priceCalc,
    ) {}

    // ════════════════════════════════════════════════════════════════════════
    // CRÉER UNE RÉSERVATION INDIVIDUELLE — HÉBERGEMENT
    // ════════════════════════════════════════════════════════════════════════

    public function createHebergementReservation(
        Users        $user,
        Promotion    $promotion,
        Hebergement  $hebergement,
        string       $dateDebutStr,
        string       $dateFinStr
    ): ReservationPromo {
        $dateDebut = new \DateTime($dateDebutStr);
        $dateFin   = new \DateTime($dateFinStr);

        if ($dateFin <= $dateDebut) {
            throw new \InvalidArgumentException('La date de départ doit être après la date d\'arrivée.');
        }

        // Vérification chevauchement (uniquement sur reservation_promo, pas sur reservation hébergement)
        $overlaps = $this->repo->findOverlappingHebergement(
            $hebergement->getIdHebergement(), $dateDebut, $dateFin
        );
        if (!empty($overlaps)) {
            throw new \InvalidArgumentException(
                'Cet hébergement est déjà réservé (via une promotion) sur ces dates.'
            );
        }

        $priceData = $this->priceCalc->calculateHebergementPrice(
            $hebergement, $dateDebut, $dateFin, $promotion
        );

        $resa = new ReservationPromo();
        $resa->setUser($user);
        $resa->setPromotion($promotion);
        $resa->setOfferType('hebergement');
        $resa->setHebergementId($hebergement->getIdHebergement());
        $resa->setDateDebut($dateDebut);
        $resa->setDateFin($dateFin);
        $resa->setPrixOriginal($priceData['base_price']);
        $resa->setReductionAppliquee($priceData['discount']);
        $resa->setMontantTotal($priceData['final_price']);
        $resa->setStatut(ReservationPromo::STATUT_EN_ATTENTE);

        $this->em->persist($resa);
        $this->em->flush();

        return $resa;
    }

    // ════════════════════════════════════════════════════════════════════════
    // CRÉER UNE RÉSERVATION INDIVIDUELLE — VOITURE
    // ════════════════════════════════════════════════════════════════════════

    public function createVoitureReservation(
        Users     $user,
        Promotion $promotion,
        Voiture   $voiture,
        float     $distanceKm,
        string    $pointDepart,
        string    $pointArrivee,
        string    $dateStr,
        int       $nbPersonnes
    ): ReservationPromo {
        if ($distanceKm <= 0) {
            throw new \InvalidArgumentException('La distance doit être positive.');
        }
        if ($nbPersonnes < 1 || $nbPersonnes > $voiture->getNb_places()) {
            throw new \InvalidArgumentException(
                sprintf('Nombre de personnes invalide (max %d places).', $voiture->getNb_places())
            );
        }

        $priceData = $this->priceCalc->calculateVoiturePrice($voiture, $distanceKm, $promotion);

        $resa = new ReservationPromo();
        $resa->setUser($user);
        $resa->setPromotion($promotion);
        $resa->setOfferType('voiture');
        $resa->setVoitureId($voiture->getId());
        $resa->setDistanceKm($distanceKm);
        $resa->setPointDepart($pointDepart);
        $resa->setPointArrivee($pointArrivee);
        $resa->setDateReservation(new \DateTime($dateStr));
        $resa->setNbPersonnes($nbPersonnes);
        $resa->setPrixOriginal($priceData['base_price']);
        $resa->setReductionAppliquee($priceData['discount']);
        $resa->setMontantTotal($priceData['final_price']);
        $resa->setStatut(ReservationPromo::STATUT_EN_ATTENTE);

        $this->em->persist($resa);
        $this->em->flush();

        return $resa;
    }

    // ════════════════════════════════════════════════════════════════════════
    // CRÉER UNE RÉSERVATION INDIVIDUELLE — ACTIVITÉ
    // ════════════════════════════════════════════════════════════════════════

    public function createActiviteReservation(
        Users     $user,
        Promotion $promotion,
        Activite  $activite,
        int       $nbPersonnes,
        string    $dateStr
    ): ReservationPromo {
        $priceData = $this->priceCalc->calculateActivitePrice($activite, $nbPersonnes, $promotion);

        $resa = new ReservationPromo();
        $resa->setUser($user);
        $resa->setPromotion($promotion);
        $resa->setOfferType('activite');
        $resa->setActiviteId($activite->getIdActivite());
        $resa->setNbPersonnes($nbPersonnes);
        $resa->setDateActivite(new \DateTime($dateStr));
        $resa->setPrixOriginal($priceData['base_price']);
        $resa->setReductionAppliquee($priceData['discount']);
        $resa->setMontantTotal($priceData['final_price']);
        $resa->setStatut(ReservationPromo::STATUT_EN_ATTENTE);

        $this->em->persist($resa);
        $this->em->flush();

        return $resa;
    }

    // ════════════════════════════════════════════════════════════════════════
    // CRÉER UNE RÉSERVATION PACK
    // ════════════════════════════════════════════════════════════════════════

    /**
     * @param array $packInputs Format: [
     *   ["type"=>"hebergement","id"=>3,"date_debut"=>"...","date_fin"=>"..."],
     *   ["type"=>"voiture","id"=>1,"distance_km"=>100,...],
     *   ...
     * ]
     */
    public function createPackReservation(
        Users     $user,
        Promotion $promotion,
        array     $packInputs
    ): ReservationPromo {
        $items      = $promotion->getPackItemsDecoded();
        $snapshot   = [];
        $totalBase  = 0.0;
        $totalFinal = 0.0;
        $totalDisc  = 0.0;

        foreach ($items as $item) {
            $type = $item['type'];
            $id   = (int)$item['id'];

            // Trouver les inputs correspondants à cet item
            $inputs = collect_input($packInputs, $type, $id);

            $priceData = match ($type) {
                'hebergement' => $this->priceCalc->calculateHebergementPrice(
                    $this->em->getRepository(Hebergement::class)->find($id),
                    new \DateTime($inputs['date_debut']),
                    new \DateTime($inputs['date_fin']),
                    $promotion
                ),
                'voiture' => $this->priceCalc->calculateVoiturePrice(
                    $this->em->getRepository(Voiture::class)->find($id),
                    (float)($inputs['distance_km'] ?? 0),
                    $promotion
                ),
                'activite' => $this->priceCalc->calculateActivitePrice(
                    $this->em->getRepository(Activite::class)->find($id),
                    (int)($inputs['nb_personnes'] ?? 1),
                    $promotion
                ),
                default => throw new \InvalidArgumentException('Type inconnu: ' . $type),
            };

            $totalBase  += $priceData['base_price'];
            $totalDisc  += $priceData['discount'];
            $totalFinal += $priceData['final_price'];

            $snapshot[] = [
                'type'      => $type,
                'id'        => $id,
                'label'     => $item['label'] ?? '',
                'inputs'    => $inputs,
                'priceData' => $priceData,
            ];
        }

        $resa = new ReservationPromo();
        $resa->setUser($user);
        $resa->setPromotion($promotion);
        $resa->setOfferType('pack');
        $resa->setPackSnapshot(json_encode($snapshot));
        $resa->setPrixOriginal(round($totalBase, 2));
        $resa->setReductionAppliquee(round($totalDisc, 2));
        $resa->setMontantTotal(round($totalFinal, 2));
        $resa->setStatut(ReservationPromo::STATUT_EN_ATTENTE);

        $this->em->persist($resa);
        $this->em->flush();

        return $resa;
    }

    // ════════════════════════════════════════════════════════════════════════
    // MODIFIER UNE RÉSERVATION (24h rule)
    // ════════════════════════════════════════════════════════════════════════

    public function updateReservation(ReservationPromo $resa, array $data): ReservationPromo
    {
        if (!$resa->isEditable()) {
            throw new \LogicException('Cette réservation ne peut plus être modifiée (délai de 24h dépassé).');
        }

        $promotion = $resa->getPromotion();

        switch ($resa->getOfferType()) {
            case 'hebergement':
                $hebergement = $this->em->getRepository(Hebergement::class)
                    ->find($resa->getHebergementId());
                $dateDebut = new \DateTime($data['date_debut']);
                $dateFin   = new \DateTime($data['date_fin']);
                if ($dateFin <= $dateDebut) {
                    throw new \InvalidArgumentException('Date de départ invalide.');
                }
                $overlaps = $this->repo->findOverlappingHebergement(
                    $resa->getHebergementId(), $dateDebut, $dateFin, $resa->getId()
                );
                if (!empty($overlaps)) {
                    throw new \InvalidArgumentException('Chevauchement de dates détecté.');
                }
                $priceData = $this->priceCalc->calculateHebergementPrice($hebergement, $dateDebut, $dateFin, $promotion);
                $resa->setDateDebut($dateDebut);
                $resa->setDateFin($dateFin);
                break;

            case 'voiture':
                $voiture   = $this->em->getRepository(Voiture::class)->find($resa->getVoitureId());
                $distKm    = (float)($data['distance_km'] ?? 0);
                $nbPers    = (int)($data['nb_personnes'] ?? 1);
                if ($distKm <= 0) throw new \InvalidArgumentException('Distance invalide.');
                if ($nbPers < 1 || $nbPers > $voiture->getNb_places()) {
                    throw new \InvalidArgumentException('Nombre de personnes invalide.');
                }
                $priceData = $this->priceCalc->calculateVoiturePrice($voiture, $distKm, $promotion);
                $resa->setDistanceKm($distKm);
                $resa->setNbPersonnes($nbPers);
                $resa->setPointDepart($data['point_depart'] ?? $resa->getPointDepart());
                $resa->setPointArrivee($data['point_arrivee'] ?? $resa->getPointArrivee());
                if (!empty($data['date_reservation'])) {
                    $resa->setDateReservation(new \DateTime($data['date_reservation']));
                }
                break;

            case 'activite':
                $activite = $this->em->getRepository(Activite::class)->find($resa->getActiviteId());
                $nbPers   = (int)($data['nb_personnes'] ?? 1);
                if ($nbPers < 1) throw new \InvalidArgumentException('Nombre de personnes invalide.');
                $priceData = $this->priceCalc->calculateActivitePrice($activite, $nbPers, $promotion);
                $resa->setNbPersonnes($nbPers);
                if (!empty($data['date_activite'])) {
                    $resa->setDateActivite(new \DateTime($data['date_activite']));
                }
                break;

            default:
                // Pack: update dates from hebergement item and recalc prices
                $snapshot = $resa->getPackSnapshotDecoded();
                if (empty($snapshot)) {
                    throw new \LogicException('Snapshot de pack introuvable.');
                }
                $totalBase  = 0.0;
                $totalDisc  = 0.0;
                $totalFinal = 0.0;
                $newSnapshot = [];

                // Get shared dates from request (hébergement dates used for whole pack)
                $sharedDateDebut = !empty($data['date_debut']) ? new \DateTime($data['date_debut']) : null;
                $sharedDateFin   = !empty($data['date_fin'])   ? new \DateTime($data['date_fin'])   : null;

                foreach ($snapshot as $item) {
                    $type = $item['type'];
                    $id   = (int)$item['id'];

                    switch ($type) {
                        case 'hebergement':
                            $heberg = $this->em->getRepository(Hebergement::class)->find($id);
                            $dDebut = $sharedDateDebut ?? new \DateTime($item['inputs']['date_debut'] ?? 'today');
                            $dFin   = $sharedDateFin   ?? new \DateTime($item['inputs']['date_fin']   ?? 'tomorrow');
                            if ($dFin <= $dDebut) throw new \InvalidArgumentException('Date de départ invalide.');
                            $pd = $this->priceCalc->calculateHebergementPrice($heberg, $dDebut, $dFin, $promotion);
                            $item['inputs']['date_debut'] = $dDebut->format('Y-m-d');
                            $item['inputs']['date_fin']   = $dFin->format('Y-m-d');
                            break;
                        case 'voiture':
                            $voiture = $this->em->getRepository(Voiture::class)->find($id);
                            $km = (float)($data['distance_km'] ?? $item['inputs']['distance_km'] ?? 50);
                            $pd = $this->priceCalc->calculateVoiturePrice($voiture, $km, $promotion);
                            $item['inputs']['distance_km'] = $km;
                            // Share date from hébergement
                            if ($sharedDateDebut) {
                                $item['inputs']['date_reservation'] = $sharedDateDebut->format('Y-m-d');
                            }
                            break;
                        case 'activite':
                            $activite = $this->em->getRepository(Activite::class)->find($id);
                            $nb = (int)($data['nb_personnes'] ?? $item['inputs']['nb_personnes'] ?? 1);
                            $pd = $this->priceCalc->calculateActivitePrice($activite, $nb, $promotion);
                            $item['inputs']['nb_personnes'] = $nb;
                            break;
                        default:
                            $pd = $item['priceData'] ?? ['base_price'=>0,'discount'=>0,'final_price'=>0];
                    }
                    $totalBase  += $pd['base_price'];
                    $totalDisc  += $pd['discount'];
                    $totalFinal += $pd['final_price'];
                    $item['priceData'] = $pd;
                    $newSnapshot[] = $item;
                }

                $resa->setPackSnapshot(json_encode($newSnapshot));
                $priceData = [
                    'base_price'  => round($totalBase, 2),
                    'discount'    => round($totalDisc, 2),
                    'final_price' => round($totalFinal, 2),
                ];
        }

        $resa->setPrixOriginal($priceData['base_price']);
        $resa->setReductionAppliquee($priceData['discount']);
        $resa->setMontantTotal($priceData['final_price']);

        $this->em->flush();
        return $resa;
    }

    // ════════════════════════════════════════════════════════════════════════
    // SUPPRIMER UNE RÉSERVATION (24h rule)
    // ════════════════════════════════════════════════════════════════════════

    public function deleteReservation(ReservationPromo $resa): void
    {
        if (!$resa->isEditable()) {
            throw new \LogicException('Cette réservation ne peut plus être supprimée (délai de 24h dépassé).');
        }
        $this->em->remove($resa);
        $this->em->flush();
    }
}

// ─── Helper function (file scope) ────────────────────────────────────────────
if (!function_exists('collect_input')) {
    function collect_input(array $packInputs, string $type, int $id): array
    {
        foreach ($packInputs as $input) {
            if (($input['type'] ?? '') === $type && (int)($input['id'] ?? 0) === $id) {
                return $input;
            }
        }
        return [];
    }
}