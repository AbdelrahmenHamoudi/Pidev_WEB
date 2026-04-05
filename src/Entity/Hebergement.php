<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;

use App\Repository\HebergementRepository;

#[ORM\Entity(repositoryClass: HebergementRepository::class)]
#[ORM\Table(name: 'hebergement')]
class Hebergement
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: "id_hebergement", type: Types::INTEGER)]
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

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $titre = null;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 1000, nullable: false)]
    private ?string $desc_hebergement = null;

    public function getDesc_hebergement(): ?string
    {
        return $this->desc_hebergement;
    }

    public function setDesc_hebergement(string $desc_hebergement): self
    {
        $this->desc_hebergement = $desc_hebergement;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $capacite = null;

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): self
    {
        $this->capacite = $capacite;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    private ?string $type_hebergement = null;

    public function getType_hebergement(): ?string
    {
        return $this->type_hebergement;
    }

    public function setType_hebergement(string $type_hebergement): self
    {
        $this->type_hebergement = $type_hebergement;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $disponible_heberg = null;

    public function isDisponible_heberg(): ?bool
    {
        return $this->disponible_heberg;
    }

    public function setDisponible_heberg(bool $disponible_heberg): self
    {
        $this->disponible_heberg = $disponible_heberg;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
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

    #[ORM\Column(type: 'string', length: 20, nullable: false)]
    private ?string $prix_par_nuit = null;

    public function getPrix_par_nuit(): ?string
    {
        return $this->prix_par_nuit;
    }

    public function setPrix_par_nuit(string $prix_par_nuit): self
    {
        $this->prix_par_nuit = $prix_par_nuit;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'hebergement')]
    private Collection $reservations;

    #[ORM\Column(name: "images", type: Types::JSON, nullable: true)]
    private ?array $images = [];

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->images = [];
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

    public function getImages(): array
    {
        return $this->images ?? [];
    }

    public function setImages(?array $images): self
    {
        $this->images = $images;
        return $this;
    }

    public function addImage(string $image): self
    {
        if ($this->images === null) {
            $this->images = [];
        }
        
        if (!in_array($image, $this->images, true)) {
            $this->images[] = $image;
        }

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

    public function setDescHebergement(string $desc_hebergement): static
    {
        $this->desc_hebergement = $desc_hebergement;

        return $this;
    }

    public function getTypeHebergement(): ?string
    {
        return $this->type_hebergement;
    }

    public function setTypeHebergement(string $type_hebergement): static
    {
        $this->type_hebergement = $type_hebergement;

        return $this;
    }

    public function isDisponibleHeberg(): ?bool
    {
        return $this->disponible_heberg;
    }

    public function setDisponibleHeberg(bool $disponible_heberg): static
    {
        $this->disponible_heberg = $disponible_heberg;

        return $this;
    }

    public function getPrixParNuit(): ?string
    {
        return $this->prix_par_nuit;
    }

    public function getPrixParNuitNumeric(): float
    {
        return (float) str_replace(',', '.', $this->prix_par_nuit);
    }

    public function setPrixParNuit(string $prix_par_nuit): static
    {
        $this->prix_par_nuit = $prix_par_nuit;

        return $this;
    }

}
