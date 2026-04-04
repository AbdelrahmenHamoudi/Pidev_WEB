<?php

namespace App\Entity;

use App\Repository\HebergementImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HebergementImageRepository::class)]
#[ORM\Table(name: 'hebergement_image')]
class HebergementImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_image', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'filename', type: 'string', length: 255)]
    private ?string $filename = null;

    #[ORM\Column(name: 'ordre', type: 'integer', nullable: true)]
    private ?int $ordre = 0;

    #[ORM\ManyToOne(targetEntity: Hebergement::class, inversedBy: 'images')]
    #[ORM\JoinColumn(name: 'hebergement_id', referencedColumnName: 'id_hebergement', nullable: false)]
    private ?Hebergement $hebergement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): static
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function getHebergement(): ?Hebergement
    {
        return $this->hebergement;
    }

    public function setHebergement(?Hebergement $hebergement): static
    {
        $this->hebergement = $hebergement;
        return $this;
    }
}
