<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Trajet;

#[ORM\Entity]
class Voiture
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_voiture;

    #[ORM\Column(type: "string", length: 255)]
    private string $marque;

    #[ORM\Column(type: "string", length: 255)]
    private string $modele;

    #[ORM\Column(type: "string", length: 50)]
    private string $immatriculation;

    #[ORM\Column(type: "string")]
    private string $prix_KM;

    #[ORM\Column(type: "boolean")]
    private bool $avec_chauffeur;

    #[ORM\Column(type: "boolean")]
    private bool $disponibilite;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "string", length: 255)]
    private string $image;

    #[ORM\Column(type: "integer")]
    private int $nb_places;

    public function getId_voiture()
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

    public function getPrix_KM()
    {
        return $this->prix_KM;
    }

    public function setPrix_KM($value)
    {
        $this->prix_KM = $value;
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

    #[ORM\OneToMany(mappedBy: "id_voiture", targetEntity: Trajet::class)]
    private Collection $trajets;

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
