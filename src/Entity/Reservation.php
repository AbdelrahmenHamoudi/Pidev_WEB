<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ReservationRepository;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id_reservation = null;

    public function getId_reservation(): ?int
    {
        return $this->id_reservation;
    }

    public function setId_reservation(int $id_reservation): self
    {
        $this->id_reservation = $id_reservation;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user ? $this->user->getId() : null;
    }

    #[ORM\ManyToOne(targetEntity: Hebergement::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'hebergement_id', referencedColumnName: 'id_hebergement')]
    private ?Hebergement $hebergement = null;

    public function getHebergement(): ?Hebergement
    {
        return $this->hebergement;
    }

    public function setHebergement(?Hebergement $hebergement): self
    {
        $this->hebergement = $hebergement;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_debut_r = null;

    public function getDate_debut_r(): ?\DateTimeInterface
    {
        return $this->date_debut_r;
    }

    public function setDate_debut_r(\DateTimeInterface $date_debut_r): self
    {
        $this->date_debut_r = $date_debut_r;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_fin_r = null;

    public function getDate_fin_r(): ?\DateTimeInterface
    {
        return $this->date_fin_r;
    }

    public function setDate_fin_r(\DateTimeInterface $date_fin_r): self
    {
        $this->date_fin_r = $date_fin_r;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    private ?string $statut_r = null;

    public function getStatut_r(): ?string
    {
        return $this->statut_r;
    }

    public function setStatut_r(string $statut_r): self
    {
        $this->statut_r = $statut_r;
        return $this;
    }

    public function getIdReservation(): ?int
    {
        return $this->id_reservation;
    }

    public function getDateDebutR(): ?\DateTime
    {
        return $this->date_debut_r;
    }

    public function setDateDebutR(?\DateTime $date_debut_r): static
    {
        $this->date_debut_r = $date_debut_r;

        return $this;
    }

    public function getDateFinR(): ?\DateTime
    {
        return $this->date_fin_r;
    }

    public function setDateFinR(?\DateTime $date_fin_r): static
    {
        $this->date_fin_r = $date_fin_r;

        return $this;
    }

    public function getStatutR(): ?string
    {
        return $this->statut_r;
    }

    public function setStatutR(string $statut_r): static
    {
        $this->statut_r = $statut_r;

        return $this;
    }

}
