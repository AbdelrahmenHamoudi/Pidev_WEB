<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

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
    private ?string $marque = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $modele = null;

    #[ORM\Column(type: "string", length: 50)]
    private ?string $immatriculation = null;

    #[ORM\Column(type: "float")]
    private ?float $prix_km = null;

    #[ORM\Column(type: "boolean")]
    private ?bool $avec_chauffeur = null;

    #[ORM\Column(type: "boolean")]
    private ?bool $disponibilite = null;

    #[ORM\Column(type: "text")]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $image = null;

    #[ORM\Column(type: "integer")]
    private ?int $nb_places = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $puissance = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $vitesse_max = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $acceleration = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $consommation = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $boite_vitesse = null;

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

    public function getPuissance(): ?int { return $this->puissance; }
    public function setPuissance(?int $v): self { $this->puissance = $v; return $this; }

    public function getVitesseMax(): ?int { return $this->vitesse_max; }
    public function setVitesseMax(?int $v): self { $this->vitesse_max = $v; return $this; }

    public function getAcceleration(): ?float { return $this->acceleration; }
    public function setAcceleration(?float $v): self { $this->acceleration = $v; return $this; }

    public function getConsommation(): ?float { return $this->consommation; }
    public function setConsommation(?float $v): self { $this->consommation = $v; return $this; }

    public function getBoiteVitesse(): ?string { return $this->boite_vitesse; }
    public function setBoiteVitesse(?string $v): self { $this->boite_vitesse = $v; return $this; }

    #[ORM\OneToMany(mappedBy: "id_voiture", targetEntity: Trajet::class, cascade: ["remove"])]
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
