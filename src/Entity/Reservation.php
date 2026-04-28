<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private int $id_reservation;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "reservations")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private $user;

    #[ORM\ManyToOne(targetEntity: Hebergement::class, inversedBy: "reservations")]
    #[ORM\JoinColumn(name: 'hebergement_id', referencedColumnName: 'id_hebergement', onDelete: 'CASCADE')]
    private ?Hebergement $hebergement_id = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $dateDebutR = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $dateFinR = null;

    #[ORM\Column(type: "string", length: 50)]
    private string $statutR = '';

    // ================================================================
    // PRIMARY KEY
    // ================================================================

    public function getId_reservation(): int
    {
        return $this->id_reservation;
    }

    public function setId_reservation($value): self
    {
        $this->id_reservation = $value;
        return $this;
    }

    /** Alias: camelCase version used by controllers */
    public function getIdReservation(): int
    {
        return $this->id_reservation;
    }

    // ================================================================
    // USER
    // ================================================================

    public function getUser_id()
    {
        return $this->user;
    }

    public function setUser_id($value): self
    {
        $this->user = $value;
        return $this;
    }

    /** Alias: controllers use getUser() */
    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user): self
    {
        $this->user = $user;
        return $this;
    }

    /** Alias: FrontendController compares getUserId() with session user_id */
    public function getUserId(): int
    {
        return is_object($this->user) ? $this->user->getId() : (int)$this->user;
    }

    // ================================================================
    // HEBERGEMENT
    // ================================================================

    public function getHebergement_id(): ?Hebergement
    {
        return $this->hebergement_id;
    }

    public function setHebergement_id($value): self
    {
        $this->hebergement_id = $value;
        return $this;
    }

    /** Alias: Twig/controllers use reservation.hebergement */
    public function getHebergement(): ?Hebergement
    {
        return $this->hebergement_id;
    }

    public function setHebergement(?Hebergement $hebergement): self
    {
        $this->hebergement_id = $hebergement;
        return $this;
    }

    // ================================================================
    // DATES
    // ================================================================

    public function getDateDebutR(): ?\DateTimeInterface
    {
        return $this->dateDebutR;
    }

    public function setDateDebutR($value): self
    {
        $this->dateDebutR = $value;
        return $this;
    }

    /** Alias: snake_case version used in FrontendController */
    public function getDate_debut_r(): ?\DateTimeInterface
    {
        return $this->dateDebutR;
    }

    public function setDate_debut_r($value): self
    {
        $this->dateDebutR = $value;
        return $this;
    }

    public function getDateFinR(): ?\DateTimeInterface
    {
        return $this->dateFinR;
    }

    public function setDateFinR($value): self
    {
        $this->dateFinR = $value;
        return $this;
    }

    /** Alias: snake_case version used in FrontendController */
    public function getDate_fin_r(): ?\DateTimeInterface
    {
        return $this->dateFinR;
    }

    public function setDate_fin_r($value): self
    {
        $this->dateFinR = $value;
        return $this;
    }

    // ================================================================
    // STATUT
    // ================================================================

    public function getStatutR(): string
    {
        return $this->statutR ?? '';
    }

    public function setStatutR($value): self
    {
        $this->statutR = $value;
        return $this;
    }

    // ================================================================
    // COMPUTED
    // ================================================================

    /**
     * Calculates price total: nightly rate × number of nights.
     * Used in admin reservation list template.
     */
    public function getPrixTotal(): ?float
    {
        if (!$this->hebergement_id || !$this->dateDebutR || !$this->dateFinR) {
            return null;
        }
        $nights = $this->dateDebutR->diff($this->dateFinR)->days;
        return $nights * (float)$this->hebergement_id->getPrixParNuit();
    }

    // ================================================================
    // SHORT ALIASES used directly in Twig templates
    // ================================================================

    /** Twig: reservation.statut — alias for statutR */
    public function getStatut(): string
    {
        return $this->statutR ?? '';
    }

    /** Twig: reservation.id — alias for id_reservation */
    public function getId(): int
    {
        return $this->id_reservation;
    }

    /** Twig: reservation.dateDebut — alias for dateDebutR */
    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebutR;
    }

    /** Twig: reservation.dateFin — alias for dateFinR */
    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFinR;
    }

    /** Twig: reservation.nombreNuits — number of nights between dates */
    public function getNombreNuits(): int
    {
        if (!$this->dateDebutR || !$this->dateFinR) {
            return 0;
        }
        return (int)$this->dateDebutR->diff($this->dateFinR)->days;
    }
}

