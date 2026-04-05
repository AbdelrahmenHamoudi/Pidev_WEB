<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\HebergementRepository;
use App\Entity\HebergementImage;

#[ORM\Entity(repositoryClass: HebergementRepository::class)]
#[ORM\Table(name: 'hebergement')]
#[ORM\HasLifecycleCallbacks]
class Hebergement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_hebergement = null;

    public function getId_hebergement(): ?int
    {
        return $this->id_hebergement;
    }

    public function setId_hebergement(int $id_hebergement): self
    {
        $this->id_hebergement = $id_hebergement;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $titre = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $desc_hebergement = null;

    public function getDesc_hebergement(): ?string
    {
        return $this->desc_hebergement;
    }

    public function setDesc_hebergement(?string $desc_hebergement): self
    {
        $this->desc_hebergement = $desc_hebergement;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $capacite = null;

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): self
    {
        $this->capacite = $capacite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $type_hebergement = null;

    public function getType_hebergement(): ?string
    {
        return $this->type_hebergement;
    }

    public function setType_hebergement(?string $type_hebergement): self
    {
        $this->type_hebergement = $type_hebergement;
        return $this;
    }

    #[ORM\Column(name: 'disponible_heberg', type: 'boolean', nullable: true)]
    private ?bool $disponible = null;

    public function isDisponible_heberg(): ?bool
    {
        return $this->disponible;
    }

    public function setDisponible_heberg(?bool $disponible_heberg): self
    {
        $this->disponible = $disponible_heberg;
        return $this;
    }

    public function getDisponible_heberg(): ?bool
    {
        return $this->disponible;
    }

    #[ORM\Column(name: 'prixParNuit', type: 'float', nullable: true)]
    private ?float $prixParNuit = null;

    public function getPrixParNuit(): ?float
    {
        return $this->prixParNuit;
    }

    public function setPrixParNuit(?float $prixParNuit): self
    {
        $this->prixParNuit = $prixParNuit;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image = null;

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: HebergementImage::class, mappedBy: 'hebergement', cascade: ['persist'])]
    private Collection $images;

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'hebergement')]
    private Collection $reservations;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    /**
     * @return Collection<int, HebergementImage>
     */
    public function getImages(): Collection
    {
        if (!$this->images instanceof Collection) {
            $this->images = new ArrayCollection();
        }
        return $this->images;
    }

    public function addImage(HebergementImage $image): self
    {
        if (!$this->getImages()->contains($image)) {
            $this->getImages()->add($image);
            $image->setHebergement($this);
        }
        return $this;
    }

    public function removeImage(HebergementImage $image): self
    {
        if ($this->getImages()->removeElement($image)) {
            if ($image->getHebergement() === $this) {
                $image->setHebergement(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        if (!$this->reservations instanceof Collection) {
            $this->reservations = new ArrayCollection();
        }
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->getReservations()->contains($reservation)) {
            $this->getReservations()->add($reservation);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        $this->getReservations()->removeElement($reservation);
        return $this;
    }

    public function getIdHebergement(): ?int
    {
        return $this->id_hebergement;
    }

    public function getDescHebergement(): ?string
    {
        return $this->desc_hebergement;
    }

    public function setDescHebergement(?string $desc_hebergement): static
    {
        $this->desc_hebergement = $desc_hebergement;
        return $this;
    }

    public function getTypeHebergement(): ?string
    {
        return $this->type_hebergement;
    }

    public function setTypeHebergement(?string $type_hebergement): static
    {
        $this->type_hebergement = $type_hebergement;
        return $this;
    }

    public function isDisponibleHeberg(): ?bool
    {
        return $this->disponible;
    }

    public function setDisponibleHeberg(?bool $disponible_heberg): static
    {
        $this->disponible = $disponible_heberg;
        return $this;
    }

    public function getDisponible(): ?bool
    {
        return $this->disponible;
    }

    public function isDisponible(): ?bool
    {
        return $this->getDisponible();
    }

    public function setDisponible(?bool $disponible): self
    {
        $this->disponible = $disponible;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id_hebergement;
    }
}
