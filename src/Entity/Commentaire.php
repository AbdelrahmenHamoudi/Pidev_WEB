<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CommentaireRepository;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ORM\Table(name: 'commentaire')]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idCommentaire = null;

    public function getIdCommentaire(): ?int
    {
        return $this->idCommentaire;
    }

    public function setIdCommentaire(int $idCommentaire): self
    {
        $this->idCommentaire = $idCommentaire;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'idPublication', referencedColumnName: 'idPublication')]
    private ?Publication $publication = null;

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): self
    {
        $this->publication = $publication;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $contenu_c = null;

    public function getContenu_c(): ?string
    {
        return $this->contenu_c;
    }

    public function setContenu_c(string $contenu_c): self
    {
        $this->contenu_c = $contenu_c;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $date_creation_c = null;

    public function getDate_creation_c(): ?string
    {
        return $this->date_creation_c;
    }

    public function setDate_creation_c(string $date_creation_c): self
    {
        $this->date_creation_c = $date_creation_c;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $statut_c = null;

    public function isStatut_c(): ?bool
    {
        return $this->statut_c;
    }

    public function setStatut_c(bool $statut_c): self
    {
        $this->statut_c = $statut_c;
        return $this;
    }

    public function getContenuC(): ?string
    {
        return $this->contenu_c;
    }

    public function setContenuC(string $contenu_c): static
    {
        $this->contenu_c = $contenu_c;

        return $this;
    }

    public function getDateCreationC(): ?string
    {
        return $this->date_creation_c;
    }

    public function setDateCreationC(string $date_creation_c): static
    {
        $this->date_creation_c = $date_creation_c;

        return $this;
    }

    public function isStatutC(): ?bool
    {
        return $this->statut_c;
    }

    public function setStatutC(bool $statut_c): static
    {
        $this->statut_c = $statut_c;

        return $this;
    }

}
