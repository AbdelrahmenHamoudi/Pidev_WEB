<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ActiviteRepository;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'activite')]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $idActivite = null;

    public function getIdActivite(): ?int
    {
        return $this->idActivite;
    }

    public function setIdActivite(int $idActivite): self
    {
        $this->idActivite = $idActivite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $lieu = null;

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): self
    {
        $this->lieu = $lieu;
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom_a = null;

    public function getNom_a(): ?string
    {
        return $this->nom_a;
    }

    public function setNom_a(string $nom_a): self
    {
        $this->nom_a = $nom_a;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $description_a = null;

    public function getDescription_a(): ?string
    {
        return $this->description_a;
    }

    public function setDescription_a(string $description_a): self
    {
        $this->description_a = $description_a;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $prix_par_personne = null;

    public function getPrix_par_personne(): ?string
    {
        return $this->prix_par_personne;
    }

    public function setPrix_par_personne(string $prix_par_personne): self
    {
        $this->prix_par_personne = $prix_par_personne;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $capacite_max = null;

    public function getCapacite_max(): ?int
    {
        return $this->capacite_max;
    }

    public function setCapacite_max(int $capacite_max): self
    {
        $this->capacite_max = $capacite_max;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Planningactivite::class, mappedBy: 'activite')]
    private Collection $planningactivites;

    public function __construct()
    {
        $this->planningactivites = new ArrayCollection();
    }

    /**
     * @return Collection<int, Planningactivite>
     */
    public function getPlanningactivites(): Collection
    {
        if (!$this->planningactivites instanceof Collection) {
            $this->planningactivites = new ArrayCollection();
        }
        return $this->planningactivites;
    }

    public function addPlanningactivite(Planningactivite $planningactivite): self
    {
        if (!$this->getPlanningactivites()->contains($planningactivite)) {
            $this->getPlanningactivites()->add($planningactivite);
        }
        return $this;
    }

    public function removePlanningactivite(Planningactivite $planningactivite): self
    {
        $this->getPlanningactivites()->removeElement($planningactivite);
        return $this;
    }

    public function getNomA(): ?string
    {
        return $this->nom_a;
    }

    public function setNomA(string $nom_a): static
    {
        $this->nom_a = $nom_a;

        return $this;
    }

    public function getDescriptionA(): ?string
    {
        return $this->description_a;
    }

    public function setDescriptionA(string $description_a): static
    {
        $this->description_a = $description_a;

        return $this;
    }

    public function getPrixParPersonne(): ?string
    {
        return $this->prix_par_personne;
    }

    public function setPrixParPersonne(string $prix_par_personne): static
    {
        $this->prix_par_personne = $prix_par_personne;

        return $this;
    }

    public function getCapaciteMax(): ?int
    {
        return $this->capacite_max;
    }

    public function setCapaciteMax(int $capacite_max): static
    {
        $this->capacite_max = $capacite_max;

        return $this;
    }

}
