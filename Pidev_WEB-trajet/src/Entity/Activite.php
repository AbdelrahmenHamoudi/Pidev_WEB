<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Activite
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idActivite;

    #[ORM\Column(type: "string", length: 255)]
    private string $lieu;

    #[ORM\Column(type: "string", length: 255)]
    private string $image;

    #[ORM\Column(type: "string", length: 255)]
    private string $nom_a;

    #[ORM\Column(type: "text")]
    private string $description_a;

    #[ORM\Column(type: "string", length: 255)]
    private string $prix_par_personne;

    #[ORM\Column(type: "integer")]
    private int $capacite_max;

    public function getIdActivite()
    {
        return $this->idActivite;
    }

    public function setIdActivite($value)
    {
        $this->idActivite = $value;
    }

    public function getLieu()
    {
        return $this->lieu;
    }

    public function setLieu($value)
    {
        $this->lieu = $value;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($value)
    {
        $this->image = $value;
    }

    public function getNom_a()
    {
        return $this->nom_a;
    }

    public function setNom_a($value)
    {
        $this->nom_a = $value;
    }

    public function getDescription_a()
    {
        return $this->description_a;
    }

    public function setDescription_a($value)
    {
        $this->description_a = $value;
    }

    public function getPrix_par_personne()
    {
        return $this->prix_par_personne;
    }

    public function setPrix_par_personne($value)
    {
        $this->prix_par_personne = $value;
    }

    public function getCapacite_max()
    {
        return $this->capacite_max;
    }

    public function setCapacite_max($value)
    {
        $this->capacite_max = $value;
    }
}
