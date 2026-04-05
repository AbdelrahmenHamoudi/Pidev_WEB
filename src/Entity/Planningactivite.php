<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PlanningactiviteRepository;

#[ORM\Entity(repositoryClass: PlanningactiviteRepository::class)]
#[ORM\Table(name: 'planningactivite')]
class Planningactivite
{
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'planningactivites')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Activite::class, inversedBy: 'planningactivites')]
    #[ORM\JoinColumn(name: 'id_activite', referencedColumnName: 'idActivite')]
    private ?Activite $activite = null;

    public function getActivite(): ?Activite
    {
        return $this->activite;
    }

    public function setActivite(?Activite $activite): self
    {
        $this->activite = $activite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $etat = null;

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): self
    {
        $this->etat = $etat;
        return $this;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id_planning = null;

    public function getId_planning(): ?int
    {
        return $this->id_planning;
    }

    public function setId_planning(int $id_planning): self
    {
        $this->id_planning = $id_planning;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $heure_debut = null;

    public function getHeure_debut(): ?string
    {
        return $this->heure_debut;
    }

    public function setHeure_debut(string $heure_debut): self
    {
        $this->heure_debut = $heure_debut;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $heure_fin = null;

    public function getHeure_fin(): ?string
    {
        return $this->heure_fin;
    }

    public function setHeure_fin(string $heure_fin): self
    {
        $this->heure_fin = $heure_fin;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_planning = null;

    public function getDate_planning(): ?\DateTimeInterface
    {
        return $this->date_planning;
    }

    public function setDate_planning(\DateTimeInterface $date_planning): self
    {
        $this->date_planning = $date_planning;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $nb_places_restantes = null;

    public function getNb_places_restantes(): ?int
    {
        return $this->nb_places_restantes;
    }

    public function setNb_places_restantes(int $nb_places_restantes): self
    {
        $this->nb_places_restantes = $nb_places_restantes;
        return $this;
    }

    public function getIdPlanning(): ?int
    {
        return $this->id_planning;
    }

    public function getHeureDebut(): ?string
    {
        return $this->heure_debut;
    }

    public function setHeureDebut(string $heure_debut): static
    {
        $this->heure_debut = $heure_debut;

        return $this;
    }

    public function getHeureFin(): ?string
    {
        return $this->heure_fin;
    }

    public function setHeureFin(string $heure_fin): static
    {
        $this->heure_fin = $heure_fin;

        return $this;
    }

    public function getDatePlanning(): ?\DateTime
    {
        return $this->date_planning;
    }

    public function setDatePlanning(\DateTime $date_planning): static
    {
        $this->date_planning = $date_planning;

        return $this;
    }

    public function getNbPlacesRestantes(): ?int
    {
        return $this->nb_places_restantes;
    }

    public function setNbPlacesRestantes(int $nb_places_restantes): static
    {
        $this->nb_places_restantes = $nb_places_restantes;

        return $this;
    }

}
