<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Planningactivite
{

    #[ORM\Column(type: "integer")]
    private int $id_utilisateur;

    #[ORM\Column(type: "integer")]
    private int $id_activite;

    #[ORM\Column(type: "string", length: 50)]
    private string $etat;

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_planning;

    #[ORM\Column(type: "string", length: 255)]
    private string $heure_debut;

    #[ORM\Column(type: "string", length: 255)]
    private string $heure_fin;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_planning;

    #[ORM\Column(type: "integer")]
    private int $nb_places_restantes;

    public function getId_utilisateur()
    {
        return $this->id_utilisateur;
    }

    public function setId_utilisateur($value)
    {
        $this->id_utilisateur = $value;
    }

    public function getId_activite()
    {
        return $this->id_activite;
    }

    public function setId_activite($value)
    {
        $this->id_activite = $value;
    }

    public function getEtat()
    {
        return $this->etat;
    }

    public function setEtat($value)
    {
        $this->etat = $value;
    }

    public function getId_planning()
    {
        return $this->id_planning;
    }

    public function setId_planning($value)
    {
        $this->id_planning = $value;
    }

    public function getHeure_debut()
    {
        return $this->heure_debut;
    }

    public function setHeure_debut($value)
    {
        $this->heure_debut = $value;
    }

    public function getHeure_fin()
    {
        return $this->heure_fin;
    }

    public function setHeure_fin($value)
    {
        $this->heure_fin = $value;
    }

    public function getDate_planning()
    {
        return $this->date_planning;
    }

    public function setDate_planning($value)
    {
        $this->date_planning = $value;
    }

    public function getNb_places_restantes()
    {
        return $this->nb_places_restantes;
    }

    public function setNb_places_restantes($value)
    {
        $this->nb_places_restantes = $value;
    }
}
