<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PublicationRepository;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Table(name: 'publication')]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idPublication = null;

    public function getIdPublication(): ?int
    {
        return $this->idPublication;
    }

    public function setIdPublication(int $idPublication): self
    {
        $this->idPublication = $idPublication;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'publications')]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $img_url = null;

    public function getImg_url(): ?string
    {
        return $this->img_url;
    }

    public function setImg_url(string $img_url): self
    {
        $this->img_url = $img_url;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $est_verifie = null;

    public function isEst_verifie(): ?bool
    {
        return $this->est_verifie;
    }

    public function setEst_verifie(bool $est_verifie): self
    {
        $this->est_verifie = $est_verifie;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $description_p = null;

    public function getDescription_p(): ?string
    {
        return $this->description_p;
    }

    public function setDescription_p(string $description_p): self
    {
        $this->description_p = $description_p;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type_cible = null;

    public function getType_cible(): ?string
    {
        return $this->type_cible;
    }

    public function setType_cible(string $type_cible): self
    {
        $this->type_cible = $type_cible;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $date_creation = null;

    public function getDate_creation(): ?string
    {
        return $this->date_creation;
    }

    public function setDate_creation(string $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $date_modif = null;

    public function getDate_modif(): ?string
    {
        return $this->date_modif;
    }

    public function setDate_modif(string $date_modif): self
    {
        $this->date_modif = $date_modif;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $statut_p = null;

    public function getStatut_p(): ?string
    {
        return $this->statut_p;
    }

    public function setStatut_p(string $statut_p): self
    {
        $this->statut_p = $statut_p;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'publication')]
    private Collection $commentaires;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        if (!$this->commentaires instanceof Collection) {
            $this->commentaires = new ArrayCollection();
        }
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->getCommentaires()->contains($commentaire)) {
            $this->getCommentaires()->add($commentaire);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        $this->getCommentaires()->removeElement($commentaire);
        return $this;
    }

    public function getImgUrl(): ?string
    {
        return $this->img_url;
    }

    public function setImgUrl(string $img_url): static
    {
        $this->img_url = $img_url;

        return $this;
    }

    public function isEstVerifie(): ?bool
    {
        return $this->est_verifie;
    }

    public function setEstVerifie(bool $est_verifie): static
    {
        $this->est_verifie = $est_verifie;

        return $this;
    }

    public function getDescriptionP(): ?string
    {
        return $this->description_p;
    }

    public function setDescriptionP(string $description_p): static
    {
        $this->description_p = $description_p;

        return $this;
    }

    public function getTypeCible(): ?string
    {
        return $this->type_cible;
    }

    public function setTypeCible(string $type_cible): static
    {
        $this->type_cible = $type_cible;

        return $this;
    }

    public function getDateCreation(): ?string
    {
        return $this->date_creation;
    }

    public function setDateCreation(string $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    public function getDateModif(): ?string
    {
        return $this->date_modif;
    }

    public function setDateModif(string $date_modif): static
    {
        $this->date_modif = $date_modif;

        return $this;
    }

    public function getStatutP(): ?string
    {
        return $this->statut_p;
    }

    public function setStatutP(string $statut_p): static
    {
        $this->statut_p = $statut_p;

        return $this;
    }

}
