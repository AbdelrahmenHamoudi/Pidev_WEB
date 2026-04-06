<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Activite;
use App\Entity\Users;

#[ORM\Entity]
class Planningactivite
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idPlanning;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "planningactivites")]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Users $id_utilisateur;

    #[ORM\ManyToOne(targetEntity: Activite::class, inversedBy: "planningactivites")]
    #[ORM\JoinColumn(name: 'id_activite', referencedColumnName: 'idActivite', onDelete: 'CASCADE')]
    private ?Activite $id_activite;

    #[ORM\Column(type: "string")]
    private string $heureDebut;

    #[ORM\Column(type: "string")]
    private string $heureFin;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $datePlanning;

    #[ORM\Column(type: "integer")]
    private int $nbPlacesRestantes;

    #[ORM\Column(type: "string", length: 50)]
    private string $etat;

    public function getIdPlanning() { return $this->idPlanning; }
    public function setIdPlanning($value) { $this->idPlanning = $value; }

    public function getId_utilisateur() { return $this->id_utilisateur; }
    public function setId_utilisateur($value) { $this->id_utilisateur = $value; }

    public function getId_activite() { return $this->id_activite; }
    public function setId_activite($value) { $this->id_activite = $value; }

    public function getHeureDebut() { return $this->heureDebut; }
    public function setHeureDebut($value) { $this->heureDebut = $value; }

    public function getHeureFin() { return $this->heureFin; }
    public function setHeureFin($value) { $this->heureFin = $value; }

    public function getDatePlanning() { return $this->datePlanning; }
    public function setDatePlanning($value) { $this->datePlanning = $value; }

    public function getNbPlacesRestantes() { return $this->nbPlacesRestantes; }
    public function setNbPlacesRestantes($value) { $this->nbPlacesRestantes = $value; }

    public function getEtat() { return $this->etat; }
    public function setEtat($value) { $this->etat = $value; }
}