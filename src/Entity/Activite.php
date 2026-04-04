<?php

namespace App\Entity;

use App\Repository\ActiviteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'activite')]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_activite')]
    private ?int $idActivite = null;

    #[ORM\Column(name: 'nom_a', length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $nomA = null;

    #[ORM\Column(name: 'description_a', type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    private ?string $descriptionA = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le lieu est obligatoire')]
    private ?string $lieu = null;

    #[ORM\Column(name: 'prix_par_personne', type: 'float')]
    #[Assert\Positive(message: 'Le prix doit etre positif')]
    private ?float $prixParPersonne = null;

    #[ORM\Column(name: 'capacite_max', type: 'integer')]
    #[Assert\Positive(message: 'La capacite doit etre positive')]
    private ?int $capaciteMax = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['Aventure', 'Sport', 'Culture', 'Detente', 'Excursion'])]
    private ?string $type = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['Disponible', 'Complet', 'Annule'])]
    private string $statut = 'Disponible';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'activite', targetEntity: Planning::class, cascade: ['persist', 'remove'])]
    private Collection $plannings;

    public function __construct()
    {
        $this->plannings = new ArrayCollection();
    }

    // ── Getters / Setters ──────────────────────────────────────────────────

    public function getIdActivite(): ?int { return $this->idActivite; }

    public function getNomA(): ?string { return $this->nomA; }
    public function setNomA(string $nomA): static { $this->nomA = $nomA; return $this; }

    public function getDescriptionA(): ?string { return $this->descriptionA; }
    public function setDescriptionA(string $descriptionA): static { $this->descriptionA = $descriptionA; return $this; }

    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(string $lieu): static { $this->lieu = $lieu; return $this; }

    public function getPrixParPersonne(): ?float { return $this->prixParPersonne; }
    public function setPrixParPersonne(float $prix): static { $this->prixParPersonne = $prix; return $this; }

    public function getCapaciteMax(): ?int { return $this->capaciteMax; }
    public function setCapaciteMax(int $capacite): static { $this->capaciteMax = $capacite; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getPlannings(): Collection { return $this->plannings; }

    public function addPlanning(Planning $planning): static
    {
        if (!$this->plannings->contains($planning)) {
            $this->plannings->add($planning);
            $planning->setActivite($this);
        }
        return $this;
    }

    public function removePlanning(Planning $planning): static
    {
        if ($this->plannings->removeElement($planning)) {
            if ($planning->getActivite() === $this) {
                $planning->setActivite(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->nomA ?? '';
    }
}
