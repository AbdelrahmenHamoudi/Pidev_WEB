<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Users;
use App\Entity\Commentaire;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class Publication
{
    #[ORM\Id]
    #[ORM\Column(name: "idPublication", type: "integer")]
    private int $idPublication;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "publications")]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Users $id_utilisateur;

    #[ORM\Column(type: "string", length: 100)]
    private string $ImgURL;

    #[ORM\Column(type: "string")]
    private string $typeCible;

    #[ORM\Column(type: "string", length: 50)]
    private string $dateCreation;

    #[ORM\Column(type: "string", length: 50)]
    private string $dateModif;

    #[ORM\Column(type: "string")]
    private string $statutP;

    #[ORM\Column(type: "boolean")]
    private bool $estVerifie;

    #[ORM\Column(type: "string", length: 200)]
    private string $DescriptionP;

    #[ORM\OneToMany(mappedBy: "idPublication", targetEntity: Commentaire::class)]
    private Collection $commentaires;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
    }

    public function getIdPublication() { return $this->idPublication; }
    public function setIdPublication($value) { $this->idPublication = $value; }

    public function getId_utilisateur() { return $this->id_utilisateur; }
    public function setId_utilisateur($value) { $this->id_utilisateur = $value; }

    public function getImgURL() { return $this->ImgURL; }
    public function setImgURL($value) { $this->ImgURL = $value; }

    public function getTypeCible() { return $this->typeCible; }
    public function setTypeCible($value) { $this->typeCible = $value; }

    public function getDateCreation() { return $this->dateCreation; }
    public function setDateCreation($value) { $this->dateCreation = $value; }

    public function getDateModif() { return $this->dateModif; }
    public function setDateModif($value) { $this->dateModif = $value; }

    public function getStatutP() { return $this->statutP; }
    public function setStatutP($value) { $this->statutP = $value; }

    public function getEstVerifie() { return $this->estVerifie; }
    public function setEstVerifie($value) { $this->estVerifie = $value; }

    public function getDescriptionP() { return $this->DescriptionP; }
    public function setDescriptionP($value) { $this->DescriptionP = $value; }

    public function getCommentaires(): Collection { return $this->commentaires; }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires[] = $commentaire;
            $commentaire->setIdPublication($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getIdPublication() === $this) {
                $commentaire->setIdPublication(null);
            }
        }
        return $this;
    }
}