<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Publication;
use App\Entity\Users;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class Commentaire
{
    #[ORM\Id]
    #[ORM\Column(name: "idCommentaire", type: "integer")]
    private int $idCommentaire;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "commentaires")]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Users $id_utilisateur;

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: "commentaires")]
    #[ORM\JoinColumn(name: 'idPublication', referencedColumnName: 'idPublication', onDelete: 'CASCADE')]
    private ?Publication $idPublication;

    #[ORM\Column(type: "string", length: 200)]
    private string $contenuC;

    #[ORM\Column(type: "string", length: 50)]
    private string $dateCreationC;

    #[ORM\Column(type: "boolean")]
    private bool $statutC;

    public function __construct()
    {
        $this->publications = new ArrayCollection();
    }

    public function getIdCommentaire() { return $this->idCommentaire; }
    public function setIdCommentaire($value) { $this->idCommentaire = $value; }

    public function getId_utilisateur() { return $this->id_utilisateur; }
    public function setId_utilisateur($value) { $this->id_utilisateur = $value; }

    public function getIdPublication() { return $this->idPublication; }
    public function setIdPublication($value) { $this->idPublication = $value; }

    public function getContenuC() { return $this->contenuC; }
    public function setContenuC($value) { $this->contenuC = $value; }

    public function getDateCreationC() { return $this->dateCreationC; }
    public function setDateCreationC($value) { $this->dateCreationC = $value; }

    public function getStatutC() { return $this->statutC; }
    public function setStatutC($value) { $this->statutC = $value; }
}