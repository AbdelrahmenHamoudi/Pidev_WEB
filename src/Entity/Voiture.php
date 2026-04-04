<?php

namespace App\Entity;

use App\Entity\Trajet;
use App\Repository\VoitureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VoitureRepository::class)]
class Voiture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_voiture')]
    private ?int $id = null;

    /**
     * @var Collection<int, Trajet>
     */
    #[ORM\OneToMany(mappedBy: 'id_voiture', targetEntity: Trajet::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $trajets;

    public function __construct()
    {
        $this->trajets = new ArrayCollection();
    }

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'La marque est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $marque = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Le modèle est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $modele = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'L immatriculation est obligatoire.')]
    #[Assert\Length(max: 50)]
    private ?string $immatriculation = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotNull(message: 'Le prix par km est obligatoire.')]
    #[Assert\Positive(message: 'Le prix par km doit être positif.')]
    private ?float $prix_KM = null;

    #[ORM\Column(nullable: true)]
    private ?bool $avec_chauffeur = null;

    #[ORM\Column(nullable: true)]
    private ?bool $disponibilite = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le nombre de places est obligatoire.')]
    #[Assert\Range(min: 1, max: 20, notInRangeMessage: 'Les places doivent être entre {{ min }} et {{ max }}.')]
    private ?int $nb_places = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(?string $marque): static
    {
        $this->marque = $marque;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(?string $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(?string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;

        return $this;
    }

    public function getPrixKM(): ?float
    {
        return $this->prix_KM;
    }

    public function setPrixKM(?float $prix_KM): static
    {
        $this->prix_KM = $prix_KM;

        return $this;
    }

    public function isAvecChauffeur(): ?bool
    {
        return $this->avec_chauffeur;
    }

    public function setAvecChauffeur(?bool $avec_chauffeur): static
    {
        $this->avec_chauffeur = $avec_chauffeur;

        return $this;
    }

    public function isDisponibilite(): ?bool
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(?bool $disponibilite): static
    {
        $this->disponibilite = $disponibilite;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getNbPlaces(): ?int
    {
        return $this->nb_places;
    }

    public function setNbPlaces(int $nb_places): static
    {
        $this->nb_places = $nb_places;

        return $this;
    }

    /**
     * @return Collection<int, Trajet>
     */
    public function getTrajets(): Collection
    {
        return $this->trajets;
    }
}
