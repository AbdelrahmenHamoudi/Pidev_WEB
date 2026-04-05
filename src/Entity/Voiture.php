<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\VoitureRepository;

#[ORM\Entity(repositoryClass: VoitureRepository::class)]
#[ORM\Table(name: 'voiture')]
class Voiture
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id_voiture = null;

    public function getId_voiture(): ?int
    {
        return $this->id_voiture;
    }

    public function setId_voiture(int $id_voiture): self
    {
        $this->id_voiture = $id_voiture;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $marque = null;

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(string $marque): self
    {
        $this->marque = $marque;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $modele = null;

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): self
    {
        $this->modele = $modele;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $immatriculation = null;

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): self
    {
        $this->immatriculation = $immatriculation;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $prix_km = null;

    public function getPrix_km(): ?string
    {
        return $this->prix_km;
    }

    public function setPrix_km(string $prix_km): self
    {
        $this->prix_km = $prix_km;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $avec_chauffeur = null;

    public function isAvec_chauffeur(): ?bool
    {
        return $this->avec_chauffeur;
    }

    public function setAvec_chauffeur(bool $avec_chauffeur): self
    {
        $this->avec_chauffeur = $avec_chauffeur;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $disponibilite = null;

    public function isDisponibilite(): ?bool
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(bool $disponibilite): self
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $image = null;

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $nb_places = null;

    public function getNb_places(): ?int
    {
        return $this->nb_places;
    }

    public function setNb_places(int $nb_places): self
    {
        $this->nb_places = $nb_places;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Trajet::class, mappedBy: 'voiture')]
    private Collection $trajets;

    public function __construct()
    {
        $this->trajets = new ArrayCollection();
    }

    /**
     * @return Collection<int, Trajet>
     */
    public function getTrajets(): Collection
    {
        if (!$this->trajets instanceof Collection) {
            $this->trajets = new ArrayCollection();
        }
        return $this->trajets;
    }

    public function addTrajet(Trajet $trajet): self
    {
        if (!$this->getTrajets()->contains($trajet)) {
            $this->getTrajets()->add($trajet);
        }
        return $this;
    }

    public function removeTrajet(Trajet $trajet): self
    {
        $this->getTrajets()->removeElement($trajet);
        return $this;
    }

    public function getIdVoiture(): ?int
    {
        return $this->id_voiture;
    }

    public function getPrixKm(): ?string
    {
        return $this->prix_km;
    }

    public function setPrixKm(string $prix_km): static
    {
        $this->prix_km = $prix_km;

        return $this;
    }

    public function isAvecChauffeur(): ?bool
    {
        return $this->avec_chauffeur;
    }

    public function setAvecChauffeur(bool $avec_chauffeur): static
    {
        $this->avec_chauffeur = $avec_chauffeur;

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

}
