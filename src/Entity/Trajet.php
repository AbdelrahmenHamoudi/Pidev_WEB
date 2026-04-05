<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Voiture;

#[ORM\Entity]
class Trajet
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private ?int $id_trajet = null;

        #[ORM\ManyToOne(targetEntity: Voiture::class, inversedBy: "trajets")]
    #[ORM\JoinColumn(name: 'id_voiture', referencedColumnName: 'id_voiture', onDelete: 'CASCADE')]
    private ?Voiture $id_voiture = null;

    #[ORM\Column(type: "integer")]
    private ?int $id_utilisateur = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $point_depart = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $point_arrivee = null;

    #[ORM\Column(type: "float")]
    private ?float $distance_km = null;

    #[ORM\Column(type: "date")]
    private ?\DateTimeInterface $date_reservation = null;

    #[ORM\Column(type: "integer")]
    private ?int $nb_personnes = null;

    #[ORM\Column(type: "string", length: 50)]
    private ?string $statut = null;

    public function getId_trajet()
    {
        return $this->id_trajet;
    }

    public function getId(): ?int
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

    // Aliases for compatibility
    public function getPointDepart() { return $this->point_depart; }
    public function setPointDepart($v) { $this->point_depart = $v; return $this; }
    public function getPointArrivee() { return $this->point_arrivee; }
    public function setPointArrivee($v) { $this->point_arrivee = $v; return $this; }
    public function getDistanceKm() { return $this->distance_km; }
    public function setDistanceKm($v) { $this->distance_km = $v; return $this; }
    public function getDateReservation() { return $this->date_reservation; }
    public function setDateReservation($v) { $this->date_reservation = $v; return $this; }
    public function getNbPersonnes() { return $this->nb_personnes; }
    public function setNbPersonnes($v) { $this->nb_personnes = $v; return $this; }
    public function getIdVoiture() { return $this->id_voiture; }
    public function setIdVoiture($v) { $this->id_voiture = $v; return $this; }
    public function getIdUtilisateur() { return $this->id_utilisateur; }
    public function setIdUtilisateur($v) { $this->id_utilisateur = $v; return $this; }
}
