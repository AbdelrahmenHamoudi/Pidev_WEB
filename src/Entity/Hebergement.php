<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Hebergement
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_hebergement;

    #[ORM\Column(type: "string", length: 255)]
    private string $titre;

    #[ORM\Column(type: "string", length: 255)]
    private string $desc_hebergement;

    #[ORM\Column(type: "integer")]
    private int $capacite;

    #[ORM\Column(type: "string", length: 100)]
    private string $type_hebergement;

    #[ORM\Column(type: "boolean")]
    private bool $disponible_heberg;

    #[ORM\Column(type: "string", length: 255)]
    private string $image;

    #[ORM\Column(type: "string", length: 255)]
    private string $prix_par_nuit;

    public function getId_hebergement()
    {
        return $this->id_hebergement;
    }

    public function setId_hebergement($value)
    {
        $this->id_hebergement = $value;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($value)
    {
        $this->titre = $value;
    }

    public function getDesc_hebergement()
    {
        return $this->desc_hebergement;
    }

    public function setDesc_hebergement($value)
    {
        $this->desc_hebergement = $value;
    }

    public function getCapacite()
    {
        return $this->capacite;
    }

    public function setCapacite($value)
    {
        $this->capacite = $value;
    }

    public function getType_hebergement()
    {
        return $this->type_hebergement;
    }

    public function setType_hebergement($value)
    {
        $this->type_hebergement = $value;
    }

    public function getDisponible_heberg()
    {
        return $this->disponible_heberg;
    }

    public function setDisponible_heberg($value)
    {
        $this->disponible_heberg = $value;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($value)
    {
        $this->image = $value;
    }

    public function getPrix_par_nuit()
    {
        return $this->prix_par_nuit;
    }

    public function setPrix_par_nuit($value)
    {
        $this->prix_par_nuit = $value;
    }
}
