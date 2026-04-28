<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\PromotionRepository::class)]
#[ORM\Table(name: 'promotion')]
#[ORM\HasLifecycleCallbacks]
class Promotion
{
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!isset($this->createdAt)) {
            $this->createdAt = new \DateTime();
        }
    }
    // ── Constantes type d'offre ──────────────────────────────────────────────
    const OFFER_HEBERGEMENT = 'hebergement';
    const OFFER_ACTIVITE    = 'activite';
    const OFFER_VOITURE     = 'voiture';

    // ── Constantes type de promo ─────────────────────────────────────────────
    const TYPE_INDIVIDUELLE = 'individuelle';
    const TYPE_PACK         = 'pack';

    // ── Constantes statut ────────────────────────────────────────────────────
    // 3 états possibles calculés à partir des dates 
    const STATUS_ACTIVE   = 'active';    // startDate <= today <= endDate
    const STATUS_UPCOMING = 'upcoming';  // startDate > today  (pas encore commencée)
    const STATUS_EXPIRED  = 'expired';   // endDate   < today  (terminée)

    // ── Champs ───────────────────────────────────────────────────────────────

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(
        min: 3, max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne doit pas dépasser {{ limit }} caractères'
    )]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'La description ne doit pas dépasser {{ limit }} caractères')]
    private ?string $description = null;

    /** 'individuelle' ou 'pack' */
    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'Le type de promotion est obligatoire')]
    #[Assert\Choice(choices: ['individuelle', 'pack'], message: 'Type invalide')]
    private string $promoType = self::TYPE_INDIVIDUELLE;

    /** Réduction en %  */
    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 1, max: 100, notInRangeMessage: 'La réduction doit être entre {{ min }}% et {{ max }}%')]
    private ?float $discountPercentage = null;

    /** Réduction montant fixe en DT */
    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le montant fixe doit être positif')]
    private ?float $discountFixed = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire')]
    #[Assert\GreaterThan(propertyPath: 'startDate', message: 'La date de fin doit être après la date de début')]
    private ?\DateTimeInterface $endDate = null;

    /** Pour individuelle : 'hebergement', 'activite', ou 'voiture' */
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $offerType = null;

    /** Pour individuelle : ID de l'offre ciblée */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $referenceId = null;

    /** Pour pack : JSON [{"type":"hebergement","id":3,"label":"..."},...] */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $packItems = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isVerrouille = false;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $views = 0;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastEngineCheck = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $autoDiscountReason = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $originalDiscountPercentage = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isAiGenerated = false;

    // ── Getters & Setters de base ─────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): self { $this->description = $d; return $this; }

    public function getPromoType(): string { return $this->promoType; }
    public function setPromoType(string $t): self { $this->promoType = $t; return $this; }

    public function isPack(): bool { return $this->promoType === self::TYPE_PACK; }

    public function getDiscountPercentage(): ?float { return $this->discountPercentage; }
    public function setDiscountPercentage(?float $v): self { $this->discountPercentage = $v; return $this; }

    public function getDiscountFixed(): ?float { return $this->discountFixed; }
    public function setDiscountFixed(?float $v): self { $this->discountFixed = $v; return $this; }

    public function getOriginalDiscountPercentage(): ?float { return $this->originalDiscountPercentage; }
    public function setOriginalDiscountPercentage(?float $v): self { $this->originalDiscountPercentage = $v; return $this; }

    public function isAiGenerated(): bool { return $this->isAiGenerated; }
    public function setIsAiGenerated(bool $v): self { $this->isAiGenerated = $v; return $this; }

    public function getStartDate(): ?\DateTimeInterface { return $this->startDate; }
    public function setStartDate(?\DateTimeInterface $d): self { $this->startDate = $d; return $this; }

    public function getEndDate(): ?\DateTimeInterface { return $this->endDate; }
    public function setEndDate(?\DateTimeInterface $d): self { $this->endDate = $d; return $this; }

    public function getOfferType(): ?string { return $this->offerType; }
    public function setOfferType(?string $v): self { $this->offerType = $v; return $this; }

    public function getReferenceId(): ?int { return $this->referenceId; }
    public function setReferenceId(?int $v): self { $this->referenceId = $v; return $this; }

    public function getPackItems(): ?string { return $this->packItems; }
    public function setPackItems(?string $v): self { $this->packItems = $v; return $this; }

    public function isVerrouille(): bool { return $this->isVerrouille; }
    public function setIsVerrouille(bool $isVerrouille): self { $this->isVerrouille = $isVerrouille; return $this; }

    public function getViews(): int { return $this->views; }
    public function setViews(int $views): self { $this->views = $views; return $this; }

    /** Retourne les items pack décodés en tableau PHP */
    public function getPackItemsDecoded(): array
    {
        if (empty($this->packItems)) return [];
        return json_decode($this->packItems, true) ?? [];
    }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $v): self { $this->image = $v; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $d): self { $this->createdAt = $d; return $this; }

    public function getLastEngineCheck(): ?\DateTimeInterface { return $this->lastEngineCheck; }
    public function setLastEngineCheck(?\DateTimeInterface $d): self { $this->lastEngineCheck = $d; return $this; }

    public function getAutoDiscountReason(): ?string { return $this->autoDiscountReason; }
    public function setAutoDiscountReason(?string $v): self { $this->autoDiscountReason = $v; return $this; }

    // ── Méthodes de STATUT ────────────────────────────────────────────────────
    //
    // CHANGEMENT PRINCIPAL :
    // L'ancien code n'avait que isActive() (true/false).
    // On ajoute maintenant 3 états : active, upcoming (bientôt), expired.
    // Ces méthodes sont utilisées dans les templates Twig.
    //
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Calcule le statut de la promotion selon les dates du jour.
     *
     * Règles :
     *   today < startDate  → upcoming  (pas encore commencée = "Bientôt")
     *   startDate <= today <= endDate  → active
     *   today > endDate    → expired
     *
     * @return string  'active' | 'upcoming' | 'expired'
     */
    public function getStatus(): string
    {
        if (!$this->startDate || !$this->endDate) {
            return self::STATUS_EXPIRED;
        }
        $today = new \DateTime('today');
        if ($today < $this->startDate) {
            return self::STATUS_UPCOMING;
        }
        if ($today > $this->endDate) {
            return self::STATUS_EXPIRED;
        }
        return self::STATUS_ACTIVE;
    }

    /**
     * Label d'affichage selon le statut.
     * Utilisé dans les cards backend et frontend.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match ($this->getStatus()) {
            self::STATUS_ACTIVE   => '✅ Active',
            self::STATUS_UPCOMING => '🕐 Bientôt',
            self::STATUS_EXPIRED  => '❌ Expirée',
            default               => '❓',
        };
    }

    /**
     * Couleurs CSS du badge selon le statut.
     * Utilisé dans les templates pour construire le style inline.
     *
     * Retourne un array avec 'bg' (couleur fond) et 'color' (couleur texte).
     *
     * @return array{bg: string, color: string}
     */
    public function getStatusColors(): array
    {
        return match ($this->getStatus()) {
            self::STATUS_ACTIVE   => ['bg' => 'rgba(26,188,156,0.12)',  'color' => '#1ABC9C'],
            self::STATUS_UPCOMING => ['bg' => 'rgba(247,183,49,0.18)',  'color' => '#E67E22'],
            self::STATUS_EXPIRED  => ['bg' => 'rgba(231,76,60,0.12)',   'color' => '#e63946'],
            default               => ['bg' => 'rgba(100,116,139,0.12)', 'color' => '#64748b'],
        };
    }

    /**
     * Rétrocompatibilité : isActive() reste disponible pour le code existant.
     * Retourne true uniquement pour STATUS_ACTIVE.
     */
    public function isActive(): bool
    {
        return $this->getStatus() === self::STATUS_ACTIVE;
    }

    /**
     * NOUVEAU — true si la promo est réservable (active OU bientôt).
     * Utilisé dans le frontend pour activer le bouton "Réserver".
     * Une promo future peut déjà être mise en avant et cliquable.
     */
    public function isReservable(): bool
    {
        return in_array($this->getStatus(), [self::STATUS_ACTIVE, self::STATUS_UPCOMING]);
    }

    // ── Helpers d'affichage ───────────────────────────────────────────────────

    /** Label lisible de la réduction (ex: "20%" ou "50 DT") */
    public function getReductionLabel(): string
    {
        if ($this->discountPercentage !== null) return $this->discountPercentage . '%';
        if ($this->discountFixed      !== null) return $this->discountFixed . ' DT';
        return 'N/A';
    }

    /** Label lisible du type d'offre (ex: "Hébergement") */
    public function getOfferTypeLabel(): string
    {
        return match ($this->offerType) {
            'hebergement' => 'Hébergement',
            'activite'    => 'Activité',
            'voiture'     => 'Voiture',
            default       => '-',
        };
    }

    /** Increment view counter */
    public function incrementViews(): self { 
        $this->views++; 
        return $this; 
    }
}