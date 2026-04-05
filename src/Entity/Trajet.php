<?php

namespace App\Entity;

use App\Repository\TrajetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_trajet')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trajets')]
    #[ORM\JoinColumn(name: 'id_voiture', referencedColumnName: 'id_voiture', nullable: false, onDelete: 'CASCADE')]
    private ?Voiture $id_voiture = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'L id utilisateur est obligatoire.')]
    #[Assert\Positive(message: 'L id utilisateur doit être un entier positif.')]
    private ?int $id_utilisateur = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Le point de départ est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $point_depart = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Le point d arrivée est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $point_arrivee = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotNull(message: 'La distance est obligatoire.')]
    #[Assert\Positive(message: 'La distance doit être positive.')]
    private ?float $distance_km = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_reservation = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le nombre de personnes est obligatoire.')]
    #[Assert\Range(min: 1, max: 20, notInRangeMessage: 'Le nombre de personnes doit être entre {{ min }} et {{ max }}.')]
    private ?int $nb_personnes = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[Assert\Length(max: 50)]
    private ?string $statut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdVoiture(): ?Voiture
    {
        return $this->id_voiture;
    }

    public function setIdVoiture(?Voiture $id_voiture): static
    {
        $this->id_voiture = $id_voiture;

        return $this;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->id_utilisateur;
    }

    public function setIdUtilisateur(int $id_utilisateur): static
    {
        $this->id_utilisateur = $id_utilisateur;

        return $this;
    }

    public function getPointDepart(): ?string
    {
        return $this->point_depart;
    }

    public function setPointDepart(?string $point_depart): static
    {
        $this->point_depart = $point_depart;

        return $this;
    }

    public function getPointArrivee(): ?string
    {
        return $this->point_arrivee;
    }

    public function setPointArrivee(?string $point_arrivee): static
    {
        $this->point_arrivee = $point_arrivee;

        return $this;
    }

    public function getDistanceKm(): ?float
    {
        return $this->distance_km;
    }

    public function setDistanceKm(?float $distance_km): static
    {
        $this->distance_km = $distance_km;

        return $this;
    }

    public function getDateReservation(): ?\DateTimeInterface
    {
        return $this->date_reservation;
    }

    public function setDateReservation(?\DateTimeInterface $date_reservation): static
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
