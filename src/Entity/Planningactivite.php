<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "planningactivite")]
class Planningactivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_planning", type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Activite::class, inversedBy: "planningactivites")]
    #[ORM\JoinColumn(name: "id_activite", 
                     referencedColumnName: "idActivite", 
                     nullable: false,
                     onDelete: 'CASCADE')]// Correction : ajouter onDelete CASCADE pour assurer l'intégrité référentielle doctrine doctor
    #[Assert\NotNull(message: "L'activité est obligatoire")]
    private ?Activite $activite = null;

    #[ORM\Column(name: "id_utilisateur", type: "integer", nullable: true)]
    private ?int $idUtilisateur = null;

    #[ORM\Column(name: "date_planning", type: "date", nullable: false)]
    #[Assert\NotNull(message: "La date est obligatoire")]
    #[Assert\GreaterThanOrEqual(
        value: "today",
        message: "La date doit être aujourd'hui ou dans le futur"
    )]
    private \DateTimeInterface $datePlanning ;

    #[ORM\Column(name: "heure_debut", type: "string", length: 10, nullable: false)]
    #[Assert\NotBlank(message: "L'heure de début est obligatoire")]
    #[Assert\Regex(
        pattern: "/^\d{2}:\d{2}$/",
        message: "Format invalide. Utilisez HH:MM"
    )]
    private string $heureDebut; 
    #[ORM\Column(name: "heure_fin", type: "string", length: 10, nullable: false)]
    #[Assert\NotBlank(message: "L'heure de fin est obligatoire")]
    #[Assert\Regex(
        pattern: "/^\d{2}:\d{2}$/",
        message: "Format invalide. Utilisez HH:MM"
    )]
    private string $heureFin;

    #[ORM\Column(name: "nb_places_restantes", type: "integer")]
    #[Assert\PositiveOrZero(message: "Le nombre de places ne peut pas être négatif")]
    #[Assert\LessThanOrEqual(
        propertyPath: "activite.capaciteMax",
        message: "Les places ne peuvent pas dépasser la capacité maximale de l'activité"
    )]
    private int $nbPlacesRestantes = 0;

    #[ORM\Column(name: "etat", type: "string", length: 50)]
    #[Assert\Choice(
        choices: ['Disponible', 'Complet', 'Annule'],
        message: "L'état doit être Disponible, Complet ou Annule"
    )]
    private string $etat = 'Disponible';

    // ─── GETTERS & SETTERS ─────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getActivite(): ?Activite { return $this->activite; }
    public function setActivite(?Activite $activite): self { $this->activite = $activite; return $this; }

    public function getIdUtilisateur(): ?int { return $this->idUtilisateur; }
    public function setIdUtilisateur(?int $idUtilisateur): self { $this->idUtilisateur = $idUtilisateur; return $this; }

    public function getDatePlanning(): ?\DateTimeInterface { return $this->datePlanning; }
    public function setDatePlanning(?\DateTimeInterface $datePlanning): self { $this->datePlanning = $datePlanning; return $this; }

    public function getHeureDebut(): ?string { return $this->heureDebut; }
    public function setHeureDebut(?string $heureDebut): self { $this->heureDebut = $heureDebut; return $this; }

    public function getHeureFin(): ?string { return $this->heureFin; }
    public function setHeureFin(?string $heureFin): self { $this->heureFin = $heureFin; return $this; }

    public function getNbPlacesRestantes(): int { return $this->nbPlacesRestantes; }
    public function setNbPlacesRestantes(int $nbPlacesRestantes): self { $this->nbPlacesRestantes = $nbPlacesRestantes; return $this; }

    public function getEtat(): string { return $this->etat; }
    public function setEtat(string $etat): self { $this->etat = $etat; return $this; }

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

    public function libererPlace(): void
    {
        $this->nbPlacesRestantes++;
        if ($this->etat === 'Complet') {
            $this->etat = 'Disponible';
        }
    }

    public function __toString(): string
    {
        return $this->datePlanning->format('d/m/Y') . ' ' . $this->heureDebut . '-' . $this->heureFin;

    }
}
