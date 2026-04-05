<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Commentaire
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idCommentaire;

    #[ORM\Column(type: "integer")]
    private int $id_utilisateur;

    #[ORM\Column(type: "integer")]
    private int $idPublication;

    #[ORM\Column(type: "string", length: 200)]
    private string $contenu_c;

    #[ORM\Column(type: "string", length: 50)]
    private string $date_creation_c;

    #[ORM\Column(type: "boolean")]
    private bool $statut_c;

    public function getIdCommentaire()
    {
        return $this->idCommentaire;
    }

    public function setIdCommentaire($value)
    {
        $this->idCommentaire = $value;
    }

    public function getId_utilisateur()
    {
        return $this->id_utilisateur;
    }

    public function setId_utilisateur($value)
    {
        $this->id_utilisateur = $value;
    }

    public function getIdPublication()
    {
        return $this->idPublication;
    }

    public function setIdPublication($value)
    {
        $this->idPublication = $value;
    }

    public function getContenu_c()
    {
        return $this->contenu_c;
    }

    public function setContenu_c($value)
    {
        $this->contenu_c = $value;
    }

    public function getDate_creation_c()
    {
        return $this->date_creation_c;
    }

    public function setDate_creation_c($value)
    {
        $this->date_creation_c = $value;
    }

    public function getStatut_c()
    {
        return $this->statut_c;
    }

    public function setStatut_c($value)
    {
        $this->statut_c = $value;
    }
}
