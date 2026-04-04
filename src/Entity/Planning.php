<?php

namespace App\Entity;

use App\Repository\PlanningRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
#[ORM\Table(name: 'planning')]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_planning')]
    private ?int $idPlanning = null;

    #[ORM\ManyToOne(targetEntity: Activite::class, inversedBy: 'plannings')]
    #[ORM\JoinColumn(name: 'id_activite', referencedColumnName: 'id_activite', nullable: false)]
    #[Assert\NotNull(message: "L'activite est obligatoire")]
    private ?Activite $activite = null;

    #[ORM\Column(name: 'date_planning', type: 'date')]
    #[Assert\NotNull(message: 'La date est obligatoire')]
    #[Assert\GreaterThanOrEqual('today', message: 'La date doit etre dans le futur')]
    private ?\DateTimeInterface $datePlanning = null;

    #[ORM\Column(name: 'heure_debut', type: 'time')]
    #[Assert\NotNull(message: "L'heure de debut est obligatoire")]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(name: 'heure_fin', type: 'time')]
    #[Assert\NotNull(message: "L'heure de fin est obligatoire")]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['Disponible', 'Complet', 'Annule'])]
    private string $etat = 'Disponible';

    #[ORM\Column(name: 'nb_places_restantes', type: 'integer')]
    #[Assert\PositiveOrZero(message: 'Le nombre de places ne peut pas etre negatif')]
    private int $nbPlacesRestantes = 0;

    // ── Getters / Setters ──────────────────────────────────────────────────

    public function getIdPlanning(): ?int { return $this->idPlanning; }

    public function getActivite(): ?Activite { return $this->activite; }
    public function setActivite(?Activite $activite): static { $this->activite = $activite; return $this; }

    public function getDatePlanning(): ?\DateTimeInterface { return $this->datePlanning; }
    public function setDatePlanning(\DateTimeInterface $date): static { $this->datePlanning = $date; return $this; }

    public function getHeureDebut(): ?\DateTimeInterface { return $this->heureDebut; }
    public function setHeureDebut(\DateTimeInterface $heure): static { $this->heureDebut = $heure; return $this; }

    public function getHeureFin(): ?\DateTimeInterface { return $this->heureFin; }
    public function setHeureFin(\DateTimeInterface $heure): static { $this->heureFin = $heure; return $this; }

    public function getEtat(): string { return $this->etat; }
    public function setEtat(string $etat): static { $this->etat = $etat; return $this; }

    public function getNbPlacesRestantes(): int { return $this->nbPlacesRestantes; }
    public function setNbPlacesRestantes(int $nb): static { $this->nbPlacesRestantes = $nb; return $this; }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isDisponible(): bool
    {
        return $this->etat === 'Disponible' && $this->nbPlacesRestantes > 0;
    }

    public function reserverPlace(): bool
    {
        if (!$this->isDisponible()) return false;
        $this->nbPlacesRestantes--;
        if ($this->nbPlacesRestantes === 0) {
            $this->etat = 'Complet';
        }
        return true;
    }

    public function __toString(): string
    {
        return $this->datePlanning?->format('d/m/Y') . ' ' .
               $this->heureDebut?->format('H:i') . '-' .
               $this->heureFin?->format('H:i');
    }
}
