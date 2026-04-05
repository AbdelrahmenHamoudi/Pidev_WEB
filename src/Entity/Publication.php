<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Publication
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idPublication;

    #[ORM\Column(type: "integer")]
    private int $id_utilisateur;

    #[ORM\Column(type: "string", length: 100)]
    private string $img_url;

    #[ORM\Column(type: "boolean")]
    private bool $est_verifie;

    #[ORM\Column(type: "string", length: 200)]
    private string $description_p;

    #[ORM\Column(type: "string", length: 255)]
    private string $type_cible;

    #[ORM\Column(type: "string", length: 50)]
    private string $date_creation;

    #[ORM\Column(type: "string", length: 50)]
    private string $date_modif;

    #[ORM\Column(type: "string", length: 255)]
    private string $statut_p;

    public function getIdPublication()
    {
        return $this->idPublication;
    }

    public function setIdPublication($value)
    {
        $this->idPublication = $value;
    }

    public function getId_utilisateur()
    {
        return $this->id_utilisateur;
    }

    public function setId_utilisateur($value)
    {
        $this->id_utilisateur = $value;
    }

    public function getImg_url()
    {
        return $this->img_url;
    }

    public function setImg_url($value)
    {
        $this->img_url = $value;
    }

    public function getEst_verifie()
    {
        return $this->est_verifie;
    }

    public function setEst_verifie($value)
    {
        $this->est_verifie = $value;
    }

    public function getDescription_p()
    {
        return $this->description_p;
    }

    public function setDescription_p($value)
    {
        $this->description_p = $value;
    }

    public function getType_cible()
    {
        return $this->type_cible;
    }

    public function setType_cible($value)
    {
        $this->type_cible = $value;
    }

    public function getDate_creation()
    {
        return $this->date_creation;
    }

    public function setDate_creation($value)
    {
        $this->date_creation = $value;
    }

    public function getDate_modif()
    {
        return $this->date_modif;
    }

    public function setDate_modif($value)
    {
        $this->date_modif = $value;
    }

    public function getStatut_p()
    {
        return $this->statut_p;
    }

    public function setStatut_p($value)
    {
        $this->statut_p = $value;
    }
}
