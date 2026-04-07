<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Users;

#[ORM\Entity]
class Trajet
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_trajet;

        #[ORM\ManyToOne(targetEntity: Voiture::class, inversedBy: "trajets")]
    #[ORM\JoinColumn(name: 'id_voiture', referencedColumnName: 'id_voiture', onDelete: 'CASCADE')]
    private Voiture $id_voiture;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "trajets")]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Users $id_utilisateur;

    #[ORM\Column(type: "string", length: 255)]
    private string $point_depart;

    #[ORM\Column(type: "string", length: 255)]
    private string $point_arrivee;

    #[ORM\Column(type: "string")]
    private string $distance_km;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_reservation;

    #[ORM\Column(type: "integer")]
    private int $nb_personnes;

    #[ORM\Column(type: "string", length: 50)]
    private string $statut;

    public function getId_trajet()
    {
        return $this->id_trajet;
    }

    public function setId_trajet($value)
    {
        $this->id_trajet = $value;
    }

    public function getId_voiture()
    {
        return $this->id_voiture;
    }

    public function setId_voiture($value)
    {
        $this->id_voiture = $value;
    }

    public function getId_utilisateur()
    {
        return $this->id_utilisateur;
    }

    public function setId_utilisateur($value)
    {
        $this->id_utilisateur = $value;
    }

    public function getPoint_depart()
    {
        return $this->point_depart;
    }

    public function setPoint_depart($value)
    {
        $this->point_depart = $value;
    }

    public function getPoint_arrivee()
    {
        return $this->point_arrivee;
    }

    public function setPoint_arrivee($value)
    {
        $this->point_arrivee = $value;
    }

    public function getDistance_km()
    {
        return $this->distance_km;
    }

    public function setDistance_km($value)
    {
        $this->distance_km = $value;
    }

    public function getDate_reservation()
    {
        return $this->date_reservation;
    }

    public function setDate_reservation($value)
    {
        $this->date_reservation = $value;
    }

    public function getNb_personnes()
    {
        return $this->nb_personnes;
    }

    public function setNb_personnes($value)
    {
        $this->nb_personnes = $value;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }
}
