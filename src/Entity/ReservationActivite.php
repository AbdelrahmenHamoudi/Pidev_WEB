<?php

namespace App\Entity;

use App\Repository\ReservationActiviteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationActiviteRepository::class)]
#[ORM\Table(name: "reservation_activite")]
#[ORM\HasLifecycleCallbacks]
class ReservationActivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    // ── Relation vers le planning réservé ──────────────────────────────────
    #[ORM\ManyToOne(targetEntity: Planningactivite::class)]
    #[ORM\JoinColumn(name: "id_planning", referencedColumnName: "id_planning", nullable: false)]
    #[Assert\NotNull(message: "Le planning est obligatoire")]
    private ?Planningactivite $planning = null;

    // ── Relation vers l'utilisateur (nullable = anonyme possible) ──────────
    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: "id_utilisateur", referencedColumnName: "id", nullable: true)]
    private ?Users $utilisateur = null;

    // ── Date de réservation (auto à la création) ───────────────────────────
    //#[ORM\Column(name: "date_reservation", type: "datetime")]
    //private ?\DateTimeInterface $dateReservation = null;
    //----------- correction doctrine doctor
    #[ORM\Column(name: "date_reservation", type: "datetime", nullable: false)]
    private \DateTimeInterface $dateReservation;


    // ── Statut ─────────────────────────────────────────────────────────────
    #[ORM\Column(name: "statut", type: "string", length: 50)]
    #[Assert\Choice(
        choices: ['En attente', 'Confirmee', 'Annulee'],
        message: "Le statut doit être En attente, Confirmee ou Annulee"
    )]
    private string $statut = 'Confirmee';

    // ── Nom + email pour les réservations anonymes ─────────────────────────
    #[ORM\Column(name: "nom_client", type: "string", length: 255, nullable: true)]
    private ?string $nomClient = null;

    #[ORM\Column(name: "email_client", type: "string", length: 255, nullable: true)]
    private ?string $emailClient = null;

    // ── Auto-set date à la création ────────────────────────────────────────
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->dateReservation = new \DateTime();
    }

    // ─── GETTERS & SETTERS ─────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getPlanning(): ?Planningactivite { return $this->planning; }
    public function setPlanning(?Planningactivite $planning): self { $this->planning = $planning; return $this; }

    public function getUtilisateur(): ?Users { return $this->utilisateur; }
    public function setUtilisateur(?Users $utilisateur): self { $this->utilisateur = $utilisateur; return $this; }

    public function getDateReservation(): ?\DateTimeInterface { return $this->dateReservation; }
    //public function setDateReservation(\DateTimeInterface $date): self { $this->dateReservation = $date; return $this; }
    //----------- correction doctrine doctor : pas de setter pour dateReservation car auto-set à la création
    // Après — protected car géré par PrePersist
    protected function setDateReservation(\DateTimeInterface $date): self
    {
        $this->dateReservation = $date;
        return $this;
    }
    
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }

    public function getNomClient(): ?string { return $this->nomClient; }
    public function setNomClient(?string $nom): self { $this->nomClient = $nom; return $this; }

    public function getEmailClient(): ?string { return $this->emailClient; }
    public function setEmailClient(?string $email): self { $this->emailClient = $email; return $this; }

    // ─── HELPERS ──────────────────────────────────────────────────────────

    public function isConfirmee(): bool { return $this->statut === 'Confirmee'; }
    public function isAnnulee(): bool   { return $this->statut === 'Annulee'; }

    /**
     * Retourne le nom du client (connecté ou anonyme)
     */
    public function getNomAffiche(): string
    {
        if ($this->utilisateur) {
            return $this->utilisateur->getPrenom() . ' ' . $this->utilisateur->getNom();
        }
        return $this->nomClient ?? 'Client anonyme';
    }

    public function getEmailAffiche(): string
    {
        if ($this->utilisateur) {
            return $this->utilisateur->getE_mail();
        }
        return $this->emailClient ?? '';
    }
}
