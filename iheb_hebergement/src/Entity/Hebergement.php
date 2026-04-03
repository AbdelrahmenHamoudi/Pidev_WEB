<?php

namespace App\Entity;

use App\Repository\HebergementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HebergementRepository::class)]
#[ORM\Table(name: 'hebergement')]
class Hebergement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_hebergement', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'titre', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le titre doit contenir au moins {{ limit }} caractères')]
    private ?string $titre = null;

    #[ORM\Column(name: 'desc_hebergement', type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'La description ne doit pas dépasser {{ limit }} caractères')]
    private ?string $descHebergement = null;

    #[ORM\Column(name: 'capacite', type: 'integer')]
    #[Assert\NotBlank(message: 'La capacité est obligatoire')]
    #[Assert\Positive(message: 'La capacité doit être un nombre positif')]
    #[Assert\LessThanOrEqual(value: 100, message: 'La capacité maximale est de {{ value }}')]
    private ?int $capacite = null;

    #[ORM\Column(name: 'type_hebergement', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le type d\'hébergement est obligatoire')]
    #[Assert\Choice(choices: ['Villa', 'Appartement', 'Maison', 'Hotel', 'Maison d\'hôte'], message: 'Veuillez sélectionner un type valide')]
    private ?string $typeHebergement = null;

    #[ORM\Column(name: 'disponible_heberg', type: 'boolean', options: ['default' => true])]
    private bool $disponible = true;

    #[ORM\Column(name: 'prixParNuit', type: 'float')]
    #[Assert\NotBlank(message: 'Le prix par nuit est obligatoire')]
    #[Assert\Positive(message: 'Le prix doit être positif')]
    #[Assert\LessThanOrEqual(value: 10000, message: 'Le prix maximum est de {{ value }} DT')]
    private ?float $prixParNuit = null;

    #[ORM\Column(name: 'image', type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'hebergement', targetEntity: Reservation::class, cascade: ['remove'])]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: 'hebergement', targetEntity: HebergementImage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $images;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescHebergement(): ?string
    {
        return $this->descHebergement;
    }

    public function setDescHebergement(?string $descHebergement): static
    {
        $this->descHebergement = $descHebergement;
        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): static
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function getTypeHebergement(): ?string
    {
        return $this->typeHebergement;
    }

    public function setTypeHebergement(string $typeHebergement): static
    {
        $this->typeHebergement = $typeHebergement;
        return $this;
    }

    public function isDisponible(): bool
    {
        return $this->disponible;
    }

    public function setDisponible(bool $disponible): static
    {
        $this->disponible = $disponible;
        return $this;
    }

    public function getPrixParNuit(): ?float
    {
        return $this->prixParNuit;
    }

    public function setPrixParNuit(float $prixParNuit): static
    {
        $this->prixParNuit = $prixParNuit;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setHebergement($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getHebergement() === $this) {
                $reservation->setHebergement(null);
            }
        }
        return $this;
    }

    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(HebergementImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setHebergement($this);
        }
        return $this;
    }

    public function removeImage(HebergementImage $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getHebergement() === $this) {
                $image->setHebergement(null);
            }
        }
        return $this;
    }

    public function getFirstImage(): ?string
    {
        $firstImage = $this->images->first();
        return $firstImage ? $firstImage->getFilename() : $this->image;
    }
}
