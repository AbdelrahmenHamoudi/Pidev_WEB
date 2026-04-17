<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\Collection;
use App\Entity\Trajet;

#[ORM\Entity]
class Voiture
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id_voiture = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "La marque est obligatoire.")]
    #[Assert\Length(min: 2, minMessage: "La marque doit comporter au moins {{ limit }} caractères.")]
    private ?string $marque = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le modèle est obligatoire.")]
    private ?string $modele = null;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\NotBlank(message: "L'immatriculation est obligatoire.")]
    #[Assert\Regex(
        pattern: "/^\d{3} TN \d{4}$/",
        message: "L'immatriculation doit respecter le format tunisien : XXX TN XXXX (Ex: 123 TN 4567)."
    )]
    private ?string $immatriculation = null;

    #[ORM\Column(type: "float")]
    #[Assert\NotBlank(message: "Le prix au km est obligatoire.")]
    #[Assert\Positive(message: "Le prix au km doit être un nombre positif.")]
    private ?float $prix_km = null;

    #[ORM\Column(type: "boolean")]
    private ?bool $avec_chauffeur = null;

    #[ORM\Column(type: "boolean")]
    private ?bool $disponibilite = null;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 10, minMessage: "La description doit comporter au moins {{ limit }} caractères.")]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $image = null;

    #[ORM\Column(type: "integer")]
    #[Assert\NotBlank(message: "Le nombre de places est obligatoire.")]
    #[Assert\Range(min: 1, max: 9, notInRangeMessage: "Le nombre de places doit être compris entre {{ min }} et {{ max }}.")]
    private ?int $nb_places = null;

    public function getId_voiture()
    {
        return $this->id_voiture;
    }

    public function getId(): ?int
    {
        return $this->id_voiture;
    }

    public function setId_voiture($value)
    {
        $this->id_voiture = $value;
    }

    public function getMarque()
    {
        return $this->marque;
    }

    public function setMarque($value)
    {
        $this->marque = $value;
    }

    public function getModele()
    {
        return $this->modele;
    }

    public function setModele($value)
    {
        $this->modele = $value;
    }

    public function getImmatriculation()
    {
        return $this->immatriculation;
    }

    public function setImmatriculation($value)
    {
        $this->immatriculation = $value;
    }

    public function getPrix_km()
    {
        return $this->prix_km;
    }

    public function setPrix_km($value)
    {
        $this->prix_km = $value;
    }

    public function getAvec_chauffeur()
    {
        return $this->avec_chauffeur;
    }

    public function setAvec_chauffeur($value)
    {
        $this->avec_chauffeur = $value;
    }

    public function getDisponibilite()
    {
        return $this->disponibilite;
    }

    public function setDisponibilite($value)
    {
        $this->disponibilite = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($value)
    {
        $this->image = $value;
    }

    public function getNb_places()
    {
        return $this->nb_places;
    }

    public function setNb_places($value)
    {
        $this->nb_places = $value;
    }

    // Aliases for compatibility
    public function getPrixKM() { return $this->prix_km; }
    public function setPrixKM($v) { $this->prix_km = $v; return $this; }
    public function getAvecChauffeur() { return $this->avec_chauffeur; }
    public function setAvecChauffeur($v) { $this->avec_chauffeur = $v; return $this; }
    public function getNbPlaces() { return $this->nb_places; }
    public function setNbPlaces($v) { $this->nb_places = $v; return $this; }

    #[ORM\OneToMany(mappedBy: "id_voiture", targetEntity: Trajet::class)]
    private Collection $trajets;

    public function __construct()
    {
        $this->trajets = new \Doctrine\Common\Collections\ArrayCollection();
    }

        public function getTrajets(): Collection
        {
            return $this->trajets;
        }
    
        public function addTrajet(Trajet $trajet): self
        {
            if (!$this->trajets->contains($trajet)) {
                $this->trajets[] = $trajet;
                $trajet->setId_voiture($this);
            }
    
            return $this;
        }
    
        public function removeTrajet(Trajet $trajet): self
        {
            if ($this->trajets->removeElement($trajet)) {
                // set the owning side to null (unless already changed)
                if ($trajet->getId_voiture() === $this) {
                    $trajet->setId_voiture(null);
                }
            }
    
            return $this;
        }
}
