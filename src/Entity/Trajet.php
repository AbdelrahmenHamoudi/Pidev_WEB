<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TrajetRepository;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
#[ORM\Table(name: 'trajet')]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id_trajet = null;

    public function getId_trajet(): ?int
    {
        return $this->id_trajet;
    }

    public function setId_trajet(int $id_trajet): self
    {
        $this->id_trajet = $id_trajet;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Voiture::class, inversedBy: 'trajets')]
    #[ORM\JoinColumn(name: 'id_voiture', referencedColumnName: 'id_voiture')]
    private ?Voiture $voiture = null;

    public function getVoiture(): ?Voiture
    {
        return $this->voiture;
    }

    public function setVoiture(?Voiture $voiture): self
    {
        $this->voiture = $voiture;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'trajets')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $point_depart = null;

    public function getPoint_depart(): ?string
    {
        return $this->point_depart;
    }

    public function setPoint_depart(string $point_depart): self
    {
        $this->point_depart = $point_depart;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $point_arrivee = null;

    public function getPoint_arrivee(): ?string
    {
        return $this->point_arrivee;
    }

    public function setPoint_arrivee(string $point_arrivee): self
    {
        $this->point_arrivee = $point_arrivee;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $distance_km = null;

    public function getDistance_km(): ?string
    {
        return $this->distance_km;
    }

    public function setDistance_km(string $distance_km): self
    {
        $this->distance_km = $distance_km;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_reservation = null;

    public function getDate_reservation(): ?\DateTimeInterface
    {
        return $this->date_reservation;
    }

    public function setDate_reservation(\DateTimeInterface $date_reservation): self
    {
        $this->date_reservation = $date_reservation;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $nb_personnes = null;

    public function getNb_personnes(): ?int
    {
        return $this->nb_personnes;
    }

    public function setNb_personnes(int $nb_personnes): self
    {
        $this->nb_personnes = $nb_personnes;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getIdTrajet(): ?int
    {
        return $this->id_trajet;
    }

    public function getPointDepart(): ?string
    {
        return $this->point_depart;
    }

    public function setPointDepart(string $point_depart): static
    {
        $this->point_depart = $point_depart;

        return $this;
    }

    public function getPointArrivee(): ?string
    {
        return $this->point_arrivee;
    }

    public function setPointArrivee(string $point_arrivee): static
    {
        $this->point_arrivee = $point_arrivee;

        return $this;
    }

    public function getDistanceKm(): ?string
    {
        return $this->distance_km;
    }

    public function setDistanceKm(string $distance_km): static
    {
        $this->distance_km = $distance_km;

        return $this;
    }

    public function getDateReservation(): ?\DateTime
    {
        return $this->date_reservation;
    }

    public function setDateReservation(\DateTime $date_reservation): static
    {
        $this->date_reservation = $date_reservation;

        return $this;
    }

    public function getNbPersonnes(): ?int
    {
        return $this->nb_personnes;
    }

    public function setNbPersonnes(int $nb_personnes): static
    {
        $this->nb_personnes = $nb_personnes;

        return $this;
    }

}
