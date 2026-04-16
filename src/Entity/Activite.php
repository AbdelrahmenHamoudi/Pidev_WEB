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
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "idActivite", type: "integer")]
    private ?int $idActivite = null;

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
    private ?string $image = null;
    // ✅ RELATION CORRIGÉE
    #[ORM\OneToMany(mappedBy: "activite", targetEntity: Planningactivite::class, cascade: ["persist", "remove"])]
    private Collection $planningactivites;

    public function __construct()
    {
        $this->planningactivites = new ArrayCollection();
    }

    // ─── GETTERS & SETTERS ─────────────────────────────

    public function getIdActivite(): ?int
    {
        return $this->idActivite;
    }

    public function getNomA(): string { return $this->nomA; }
    public function setNomA(string $nomA): self { $this->nomA = $nomA; return $this; }

    public function getDescriptionA(): string { return $this->descriptionA; }
    public function setDescriptionA(string $descriptionA): self { $this->descriptionA = $descriptionA; return $this; }

    public function getLieu(): string { return $this->lieu; }
    public function setLieu(string $lieu): self { $this->lieu = $lieu; return $this; }

    public function getPrixParPersonne(): string { return $this->prixParPersonne; }
    public function setPrixParPersonne(string $prix): self { $this->prixParPersonne = $prix; return $this; }

    public function getCapaciteMax(): int { return $this->capaciteMax; }
    public function setCapaciteMax(int $capaciteMax): self { $this->capaciteMax = $capaciteMax; return $this; }

    public function getImage(): ?string
{
    return $this->image;
}

public function setImage(?string $image): self
{
    $this->image = $image;
    return $this;
}

    // ─── RELATION ─────────────────────────────────────

    public function getPlanningactivites(): Collection
    {
        return $this->planningactivites;
    }

    public function addPlanningactivite(Planning $planning): self
    {
        if (!$this->planningactivites->contains($planning)) {
            $this->planningactivites[] = $planning;
            $planning->setActivite($this);
        }
        return $this;
    }

    public function removePlanningactivite(Planning $planning): self
    {
        if ($this->planningactivites->removeElement($planning)) {
            if ($planning->getActivite() === $this) {
                $planning->setActivite(null);
            }
        }
        return $this;
    }
    public function getPlannings(): Collection
{
    return $this->planningactivites; // ou le nom exact de ta propriété existante
}

#[ORM\Column(type: "string", length: 255)]
private ?string $type = null;

public function getType(): ?string
{
    return $this->type;
}

public function setType(?string $type): self
{
    $this->type = $type;
    return $this;
}
#[ORM\Column(type: "string", length: 255, nullable: true)]
private ?string $statut = null;

public function getStatut(): ?string
{
    return $this->statut;
}

public function setStatut(?string $statut): self
{
    $this->statut = $statut;
    return $this;
}
}