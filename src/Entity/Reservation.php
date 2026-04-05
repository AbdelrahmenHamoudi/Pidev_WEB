<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Reservation
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_reservation;

    #[ORM\Column(type: "integer")]
    private int $user_id;

    #[ORM\Column(type: "integer")]
    private int $hebergement_id;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_debut_r;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_fin_r;

    #[ORM\Column(type: "string", length: 50)]
    private string $statut_r;

    public function getId_reservation()
    {
        return $this->id_reservation;
    }

    public function setId_reservation($value)
    {
        $this->id_reservation = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getHebergement_id()
    {
        return $this->hebergement_id;
    }

    public function setHebergement_id($value)
    {
        $this->hebergement_id = $value;
    }

    public function getDate_debut_r()
    {
        return $this->date_debut_r;
    }

    public function setDate_debut_r($value)
    {
        $this->date_debut_r = $value;
    }

    public function getDate_fin_r()
    {
        return $this->date_fin_r;
    }

    public function setDate_fin_r($value)
    {
        $this->date_fin_r = $value;
    }

    public function getStatut_r()
    {
        return $this->statut_r;
    }

    public function setStatut_r($value)
    {
        $this->statut_r = $value;
    }
}
