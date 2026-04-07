<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "planningactivite")]
class Planningactivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_planning", type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Activite::class, inversedBy: "planningactivites")]
    #[ORM\JoinColumn(name: "id_activite", referencedColumnName: "idActivite")]
    private ?Activite $activite = null;

    #[ORM\Column(name: "id_utilisateur", type: "integer", nullable: true)]
    private ?int $idUtilisateur = null;

    #[ORM\Column(name: "date_planning", type: "date")]
    private ?\DateTimeInterface $datePlanning = null;

    #[ORM\Column(name: "heure_debut", type: "string", length: 10)]
    private ?string $heureDebut = null;

    #[ORM\Column(name: "heure_fin", type: "string", length: 10)]
    private ?string $heureFin = null;

    #[ORM\Column(name: "nb_places_restantes", type: "integer")]
    private int $nbPlacesRestantes = 0;

    #[ORM\Column(name: "etat", type: "string", length: 50)]
    private string $etat = 'Disponible';

    // ─── GETTERS & SETTERS ─────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActivite(): ?Activite
    {
        return $this->activite;
    }

    public function setActivite(?Activite $activite): self
    {
        $this->activite = $activite;
        return $this;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->idUtilisateur;
    }

    public function setIdUtilisateur(?int $idUtilisateur): self
    {
        $this->idUtilisateur = $idUtilisateur;
        return $this;
    }

    public function getDatePlanning(): ?\DateTimeInterface
    {
        return $this->datePlanning;
    }

    public function setDatePlanning(?\DateTimeInterface $datePlanning): self
    {
        $this->datePlanning = $datePlanning;
        return $this;
    }

    public function getHeureDebut(): ?string
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?string $heureDebut): self
    {
        $this->heureDebut = $heureDebut;
        return $this;
    }

    public function getHeureFin(): ?string
    {
        return $this->heureFin;
    }

    public function setHeureFin(?string $heureFin): self
    {
        $this->heureFin = $heureFin;
        return $this;
    }

    public function getNbPlacesRestantes(): int
    {
        return $this->nbPlacesRestantes;
    }

    public function setNbPlacesRestantes(int $nbPlacesRestantes): self
    {
        $this->nbPlacesRestantes = $nbPlacesRestantes;
        return $this;
    }

    public function getEtat(): string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): self
    {
        $this->etat = $etat;
        return $this;
    }

    // ─── METIER ───────────────────────────────────────

    public function isDisponible(): bool
    {
        return $this->etat === 'Disponible' && $this->nbPlacesRestantes > 0;
    }

    public function reserverPlace(): void
    {
        if ($this->nbPlacesRestantes > 0) {
            $this->nbPlacesRestantes--;
        }

        if ($this->nbPlacesRestantes === 0) {
            $this->etat = 'Complet';
        }
    }
}