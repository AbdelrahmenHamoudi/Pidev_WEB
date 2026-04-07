<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Planningactivite;

#[ORM\Entity]
class Activite
{
    #[ORM\Id]
    #[ORM\Column(name: "idActivite", type: "integer")]
    private int $idActivite;

    #[ORM\Column(type: "string", length: 255)]
    private string $nomA;

    #[ORM\Column(type: "text")]
    private string $descriptionA;

    #[ORM\Column(type: "string", length: 255)]
    private string $lieu;

    #[ORM\Column(type: "string", length: 255)]
    private string $prixParPersonne;

    #[ORM\Column(type: "integer")]
    private int $capaciteMax;

    #[ORM\Column(type: "string", length: 255)]
    private string $image;

    #[ORM\OneToMany(mappedBy: "id_activite", targetEntity: Planningactivite::class)]
    private Collection $planningactivites;

    public function __construct()
    {
        $this->planningactivites = new ArrayCollection();
    }

    public function getIdActivite() { return $this->idActivite; }
    public function setIdActivite($value) { $this->idActivite = $value; }

    public function getNomA() { return $this->nomA; }
    public function setNomA($value) { $this->nomA = $value; }

    public function getDescriptionA() { return $this->descriptionA; }
    public function setDescriptionA($value) { $this->descriptionA = $value; }

    public function getLieu() { return $this->lieu; }
    public function setLieu($value) { $this->lieu = $value; }

    public function getPrixParPersonne() { return $this->prixParPersonne; }
    public function setPrixParPersonne($value) { $this->prixParPersonne = $value; }

    public function getCapaciteMax() { return $this->capaciteMax; }
    public function setCapaciteMax($value) { $this->capaciteMax = $value; }

    public function getImage() { return $this->image; }
    public function setImage($value) { $this->image = $value; }

    public function getPlanningactivites(): Collection { return $this->planningactivites; }

    public function addPlanningactivite(Planningactivite $planningactivite): self
    {
        if (!$this->planningactivites->contains($planningactivite)) {
            $this->planningactivites[] = $planningactivite;
            $planningactivite->setId_activite($this);
        }
        return $this;
    }

    public function removePlanningactivite(Planningactivite $planningactivite): self
    {
        if ($this->planningactivites->removeElement($planningactivite)) {
            if ($planningactivite->getId_activite() === $this) {
                $planningactivite->setId_activite(null);
            }
        }
        return $this;
    }
}