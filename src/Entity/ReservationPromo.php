<?php

namespace App\Entity;

use App\Repository\ReservationPromoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * 
 */
#[ORM\Entity(repositoryClass: ReservationPromoRepository::class)]
#[ORM\Table(name: 'reservation_promo')]
#[ORM\HasLifecycleCallbacks]
class ReservationPromo
{
    // ── Statuts ─────────────────────────────────────────────────────────────
    const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    const STATUT_CONFIRMEE  = 'CONFIRMEE';
    const STATUT_ANNULEE    = 'ANNULEE';

    // ── Clé primaire ─────────────────────────────────────────────────────────
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // ── Liens métier ──────────────────────────────────────────────────────────

    /** Utilisateur qui réserve */
    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Users $user = null;

    /** Promotion ciblée */
    #[ORM\ManyToOne(targetEntity: Promotion::class)]
    #[ORM\JoinColumn(name: 'promotion_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Promotion $promotion = null;

    // ── Type de l'offre réservée ──────────────────────────────────────────────
    /** 'hebergement' | 'voiture' | 'activite' | 'pack' */
    #[ORM\Column(type: 'string', length: 20)]
    private string $offerType = 'hebergement';

    // ── Champs HÉBERGEMENT ───────────────────────────────────────────────────
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $hebergementId = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    // ── Champs VOITURE ───────────────────────────────────────────────────────
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $voitureId = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $distanceKm = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $pointDepart = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $pointArrivee = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbPersonnes = null;

    // ── Champs ACTIVITÉ ──────────────────────────────────────────────────────
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $activiteId = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateActivite = null;

    // ── Champs PACK ──────────────────────────────────────────────────────────
    /**
     * JSON snapshot des offres dans le pack au moment de la réservation.
     * Format : [{"type":"hebergement","id":3,"label":"...","priceData":{...},"inputs":{...}}, ...]
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $packSnapshot = null;

    // ── Prix calculés ────────────────────────────────────────────────────────
    #[ORM\Column(type: 'float')]
    private float $prixOriginal = 0.0;

    #[ORM\Column(type: 'float')]
    private float $reductionAppliquee = 0.0;

    #[ORM\Column(type: 'float')]
    private float $montantTotal = 0.0;

    // ── Statut & Timestamps ──────────────────────────────────────────────────
    #[ORM\Column(type: 'string', length: 20)]
    private string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    // ════════════════════════════════════════════════════════════════════════
    // LIFECYCLE
    // ════════════════════════════════════════════════════════════════════════

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // ════════════════════════════════════════════════════════════════════════
    // BUSINESS RULES
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Une réservation peut être modifiée/supprimée uniquement dans les 24h suivant sa création.
     */
    public function isEditable(): bool
    {
        if (!isset($this->createdAt)) return true;
        $now      = new \DateTime();
        $deadline = (clone $this->createdAt)->modify('+24 hours');
        return $now <= $deadline;
    }

    /**
     * Retourne le nombre d'heures restantes pour modifier (0 si délai dépassé).
     */
    public function getRemainingEditHours(): int
    {
        if (!isset($this->createdAt)) return 24;
        $now      = new \DateTime();
        $deadline = (clone $this->createdAt)->modify('+24 hours');
        $diff     = $now->diff($deadline);
        if ($diff->invert) return 0;
        return ($diff->h + $diff->days * 24);
    }

    /**
     * Décoder le packSnapshot en tableau PHP.
     */
    public function getPackSnapshotDecoded(): array
    {
        if (empty($this->packSnapshot)) return [];
        return json_decode($this->packSnapshot, true) ?? [];
    }

    // ════════════════════════════════════════════════════════════════════════
    // GETTERS / SETTERS
    // ════════════════════════════════════════════════════════════════════════

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?Users { return $this->user; }
    public function setUser(?Users $user): self { $this->user = $user; return $this; }

    public function getPromotion(): ?Promotion { return $this->promotion; }
    public function setPromotion(?Promotion $p): self { $this->promotion = $p; return $this; }

    public function getOfferType(): string { return $this->offerType; }
    public function setOfferType(string $t): self { $this->offerType = $t; return $this; }

    // -- hébergement --
    public function getHebergementId(): ?int { return $this->hebergementId; }
    public function setHebergementId(?int $v): self { $this->hebergementId = $v; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeInterface $d): self { $this->dateDebut = $d; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $d): self { $this->dateFin = $d; return $this; }

    // -- voiture --
    public function getVoitureId(): ?int { return $this->voitureId; }
    public function setVoitureId(?int $v): self { $this->voitureId = $v; return $this; }

    public function getDistanceKm(): ?float { return $this->distanceKm; }
    public function setDistanceKm(?float $v): self { $this->distanceKm = $v; return $this; }

    public function getPointDepart(): ?string { return $this->pointDepart; }
    public function setPointDepart(?string $v): self { $this->pointDepart = $v; return $this; }

    public function getPointArrivee(): ?string { return $this->pointArrivee; }
    public function setPointArrivee(?string $v): self { $this->pointArrivee = $v; return $this; }

    public function getDateReservation(): ?\DateTimeInterface { return $this->dateReservation; }
    public function setDateReservation(?\DateTimeInterface $d): self { $this->dateReservation = $d; return $this; }

    public function getNbPersonnes(): ?int { return $this->nbPersonnes; }
    public function setNbPersonnes(?int $v): self { $this->nbPersonnes = $v; return $this; }

    // -- activité --
    public function getActiviteId(): ?int { return $this->activiteId; }
    public function setActiviteId(?int $v): self { $this->activiteId = $v; return $this; }

    public function getDateActivite(): ?\DateTimeInterface { return $this->dateActivite; }
    public function setDateActivite(?\DateTimeInterface $d): self { $this->dateActivite = $d; return $this; }

    // -- pack --
    public function getPackSnapshot(): ?string { return $this->packSnapshot; }
    public function setPackSnapshot(?string $v): self { $this->packSnapshot = $v; return $this; }

    // -- prix --
    public function getPrixOriginal(): float { return $this->prixOriginal; }
    public function setPrixOriginal(float $v): self { $this->prixOriginal = $v; return $this; }

    public function getReductionAppliquee(): float { return $this->reductionAppliquee; }
    public function setReductionAppliquee(float $v): self { $this->reductionAppliquee = $v; return $this; }

    public function getMontantTotal(): float { return $this->montantTotal; }
    public function setMontantTotal(float $v): self { $this->montantTotal = $v; return $this; }

    // -- statut --
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $v): self { $this->statut = $v; return $this; }

    // -- timestamps --
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
}