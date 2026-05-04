<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Reservation;

#[ORM\Entity(repositoryClass: \App\Repository\HebergementRepository::class)]
class Hebergement
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: "integer")]
    private int $id_hebergement;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $desc_hebergement = null;

    #[ORM\Column(type: "integer")]
    private ?int $capacite = null;

    #[ORM\Column(type: "string", length: 100)]
    private ?string $type_hebergement = null;

    #[ORM\Column(type: "boolean")]
    private ?bool $disponible_heberg = null;

    #[ORM\Column(type: "string")]
    private ?string $prixParNuit = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $image = null;

    public function getId_hebergement(): int
    {
        return $this->id_hebergement;
    }

    public function setId_hebergement(int $value): void
    {
        $this->id_hebergement = $value;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $value): void
    {
        $this->titre = $value;
    }

    public function getDesc_hebergement(): ?string
    {
        return $this->desc_hebergement;
    }

    public function setDesc_hebergement(?string $value): void
    {
        $this->desc_hebergement = $value;
    }

    /** Alias for desc_hebergement used by forms */
    public function getDescHebergement(): string
    {
        return $this->desc_hebergement;
    }

    public function setDescHebergement(string $value): self
    {
        $this->desc_hebergement = $value;
        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $value): void
    {
        $this->capacite = $value;
    }

    public function getType_hebergement(): ?string
    {
        return $this->type_hebergement;
    }

    public function setType_hebergement(?string $value): void
    {
        $this->type_hebergement = $value;
    }

    public function getDisponible_heberg(): ?bool
    {
        return $this->disponible_heberg;
    }

    public function setDisponible_heberg(?bool $value): void
    {
        $this->disponible_heberg = $value;
    }

    public function getPrixParNuit(): ?string
    {
        return $this->prixParNuit;
    }

    public function setPrixParNuit(?string $value): void
    {
        $this->prixParNuit = $value;
    }

    public function getPrixParNuitNumeric(): float
    {
        return (float) str_replace(',', '.', $this->prixParNuit ?? '0');
    }

    public function getImage(): string
    {
        return $this->image ?? '';
    }

    public function setImage(?string $value): self
    {
        $this->image = $value;
        return $this;
    }

    // ---- Alias helpers expected by templates & controllers ----

    /** Returns the primary key (alias for id_hebergement) */
    public function getId(): int
    {
        return $this->id_hebergement;
    }

    /** Alias used by controller CSRF tokens */
    public function getIdHebergement(): int
    {
        return $this->id_hebergement;
    }

    /** Twig: hebergement.typeHebergement */
    public function getTypeHebergement(): string
    {
        return $this->type_hebergement;
    }

    public function setTypeHebergement(?string $value): self
    {
        $this->type_hebergement = $value;
        return $this;
    }

    /** Twig: hebergement.disponible */
    public function isDisponible(): bool
    {
        return $this->disponible_heberg;
    }

    public function getDisponible(): bool
    {
        return $this->disponible_heberg;
    }

    /** Used by FrontendController and HebergementType form */
    public function isDisponibleHeberg(): bool
    {
        return $this->disponible_heberg;
    }

    public function getDisponibleHeberg(): bool
    {
        return $this->disponible_heberg;
    }

    public function setDisponibleHeberg(bool $value): self
    {
        $this->disponible_heberg = $value;
        return $this;
    }

    /**
     * Returns images as an iterable of simple objects with a `filename` property.
     * The entity stores a single image string; this wraps it so the carousel
     * template can loop over `hebergement.images` without any DB changes.
     *
     * @return array<object{filename: string}>
     */
    /**
     * Returns images as an iterable of simple objects with a `filename` property.
     * Checks if a folder for this accommodation exists, otherwise falls back to the main image.
     *
     * @return array<object{filename: string}>
     */
    public function getImages(): array
    {
        $images = [];
        $id = $this->getId_hebergement();

        // 1. Try to scan the specific folder for this ID (supports "unlimited" images)
        $dirPath = 'uploads/hebergements/' . $id;

        // Build absolute path to project root and normalize slashes for glob()
        $projectRoot = str_replace('\\', '/', dirname(__DIR__, 2));
        $fullSystemPath = $projectRoot . '/public/' . $dirPath;

        if (is_dir($fullSystemPath)) {
            // Use forward slashes for glob pattern, even on Windows
            $pattern = $fullSystemPath . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';
            $files = glob($pattern, GLOB_BRACE);

            if ($files) {
                foreach ($files as $file) {
                    $images[] = new class ($dirPath . '/' . basename($file)) {
                        public string $filename;
                        public function __construct(string $f)
                        {
                            $this->filename = $f; }
                        public function getFilename(): string
                        {
                            return $this->filename; }
                    };
                }
            }
        }

        // 2. Fallback to the legacy single image field if folder is empty or non-existent
        if (empty($images) && !empty($this->image)) {
            $images[] = new class ($this->image) {
                public string $filename;
                public function __construct(string $f)
                {
                    $this->filename = $f; }
                public function getFilename(): string
                {
                    return $this->filename; }
            };
        }

        return $images;
    }

    /**
     * Adds an image path (stores only the last added as the main image).
     * Used by BackendController during upload.
     */
    public function addImage(string $imagePath): self
    {
        // Single-image storage: keep the first upload as the primary image
        if (empty($this->image)) {
            $this->image = $imagePath;
        }
        return $this;
    }

    #[ORM\OneToMany(mappedBy: "hebergement_id", targetEntity: Reservation::class)]
    private Collection $reservations;

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setHebergement_id($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getHebergement_id() === $this) {
                $reservation->setHebergement_id(null);
            }
        }

        return $this;
    }

}
