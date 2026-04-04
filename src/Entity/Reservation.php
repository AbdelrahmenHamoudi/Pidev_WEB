<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reservation', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    private ?int $userId = null;

    #[ORM\ManyToOne(targetEntity: Hebergement::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'hebergement_id', referencedColumnName: 'id_hebergement', nullable: false)]
    #[Assert\NotBlank(message: 'Veuillez sélectionner un hébergement')]
    private ?Hebergement $hebergement = null;

    #[ORM\Column(name: 'dateDebutR', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    #[Assert\GreaterThanOrEqual('today', message: 'La date de début doit être aujourd\'hui ou dans le futur')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'dateFinR', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire')]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: 'statutR', type: 'string', length: 50, options: ['default' => 'EN ATTENTE'])]
    #[Assert\Choice(choices: ['EN ATTENTE', 'Confirmée', 'Annulée', 'PENDING'], message: 'Statut invalide')]
    private ?string $statut = 'EN ATTENTE';

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->dateDebut && $this->dateFin) {
            if ($this->dateFin <= $this->dateDebut) {
                $context->buildViolation('La date de fin doit être après la date de début')
                    ->atPath('dateFin')
                    ->addViolation();
            }

            $diff = $this->dateDebut->diff($this->dateFin);
            if ($diff->days > 30) {
                $context->buildViolation('La réservation ne peut pas dépasser 30 nuits')
                    ->atPath('dateFin')
                    ->addViolation();
            }
        }

        if ($this->hebergement && !$this->hebergement->isDisponible()) {
            $context->buildViolation('Cet hébergement n\'est pas disponible')
                ->atPath('hebergement')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getHebergement(): ?Hebergement
    {
        return $this->hebergement;
    }

    public function setHebergement(?Hebergement $hebergement): static
    {
        $this->hebergement = $hebergement;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getNombreNuits(): int
    {
        if ($this->dateDebut && $this->dateFin) {
            return $this->dateDebut->diff($this->dateFin)->days;
        }
        return 0;
    }

    public function getPrixTotal(): float
    {
        if ($this->hebergement) {
            return $this->getNombreNuits() * $this->hebergement->getPrixParNuit();
        }
        return 0;
    }
}
