<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $nom;

    #[ORM\Column(type: "string", length: 255)]
    private string $prenom;

    #[ORM\Column(type: "string", length: 255)]
    private string $date_naiss;

    #[ORM\Column(type: "string", length: 255)]
    private string $e_mail;

    #[ORM\Column(type: "string", length: 20)]
    private string $num_tel;

    #[ORM\Column(type: "string", length: 255)]
    private string $mot_de_pass;

    #[ORM\Column(type: "string", length: 255)]
    private string $image = 'default.png';

    #[ORM\Column(type: "string")]
    private string $role = 'user';

    #[ORM\Column(type: "string")]
    private string $status = 'Unbanned';

    #[ORM\Column(type: "boolean")]
    private bool $email_verified = false;

    #[ORM\OneToMany(mappedBy: "admin_id", targetEntity: Admin_action_logs::class)]
    private Collection $admin_action_logss;

    #[ORM\OneToMany(mappedBy: "user_id", targetEntity: Connexion_logs::class)]
    private Collection $connexion_logss;

    #[ORM\OneToMany(mappedBy: "user_id", targetEntity: Email_verification_tokens::class)]
    private Collection $email_verification_tokenss;

    #[ORM\OneToMany(mappedBy: "user_id", targetEntity: Notification::class)]
    private Collection $notifications;

    #[ORM\OneToMany(mappedBy: "user_id", targetEntity: User_2fa::class)]
    private Collection $user_2fas;

    #[ORM\OneToMany(mappedBy: "id_utilisateur", targetEntity: Commentaire::class)]
    private Collection $commentaires;

    #[ORM\OneToMany(mappedBy: "id_utilisateur", targetEntity: Planningactivite::class)]
    private Collection $planningactivites;

    #[ORM\OneToMany(mappedBy: "id_utilisateur", targetEntity: Publication::class)]
    private Collection $publications;

    #[ORM\OneToMany(mappedBy: "user", targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: "id_utilisateur", targetEntity: Trajet::class)]
    private Collection $trajets;

    public function __construct()
    {
        $this->admin_action_logss = new ArrayCollection();
        $this->connexion_logss = new ArrayCollection();
        $this->email_verification_tokenss = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->user_2fas = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->planningactivites = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->trajets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $value): self
    {
        $this->nom = trim($value);
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $value): self
    {
        $this->prenom = trim($value);
        return $this;
    }

    public function getDate_naiss(): ?string
    {
        return $this->date_naiss;
    }

    public function setDate_naiss(string $value): self
    {
        $this->date_naiss = trim($value);
        return $this;
    }

    public function getE_mail(): ?string
    {
        return $this->e_mail;
    }

    public function setE_mail(string $value): self
    {
        $this->e_mail = strtolower(trim($value));
        return $this;
    }

    public function getNum_tel(): ?string
    {
        return $this->num_tel;
    }

    public function setNum_tel(string $value): self
    {
        $this->num_tel = trim($value);
        return $this;
    }

    public function getMot_de_pass(): ?string
    {
        return $this->mot_de_pass;
    }

    public function setMot_de_pass(string $value): self
    {
        $this->mot_de_pass = $value;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $value): self
    {
        $this->image = trim($value);
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $value): self
    {
        $allowedRoles = ['user', 'admin'];

        if (!in_array($value, $allowedRoles, true)) {
            throw new \InvalidArgumentException('Rôle invalide.');
        }

        $this->role = $value;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $value): self
    {
        $allowedStatuses = ['Banned', 'Unbanned'];

        if (!in_array($value, $allowedStatuses, true)) {
            throw new \InvalidArgumentException('Statut invalide.');
        }

        $this->status = $value;
        return $this;
    }

    public function getEmail_verified(): bool
    {
        return $this->email_verified;
    }

    public function setEmail_verified(bool $value): self
    {
        $this->email_verified = $value;
        return $this;
    }

    public function getAdmin_action_logss(): Collection
    {
        return $this->admin_action_logss;
    }

    public function addAdmin_action_logs(Admin_action_logs $admin_action_logs): self
    {
        if (!$this->admin_action_logss->contains($admin_action_logs)) {
            $this->admin_action_logss[] = $admin_action_logs;
            $admin_action_logs->setAdmin_id($this);
        }

        return $this;
    }

    public function removeAdmin_action_logs(Admin_action_logs $admin_action_logs): self
    {
        if ($this->admin_action_logss->removeElement($admin_action_logs)) {
            if ($admin_action_logs->getAdmin_id() === $this) {
                $admin_action_logs->setAdmin_id(null);
            }
        }

        return $this;
    }

    public function getConnexion_logss(): Collection
    {
        return $this->connexion_logss;
    }

    public function addConnexion_logs(Connexion_logs $connexion_logs): self
    {
        if (!$this->connexion_logss->contains($connexion_logs)) {
            $this->connexion_logss[] = $connexion_logs;
            $connexion_logs->setUser_id($this);
        }

        return $this;
    }

    public function removeConnexion_logs(Connexion_logs $connexion_logs): self
    {
        if ($this->connexion_logss->removeElement($connexion_logs)) {
            if ($connexion_logs->getUser_id() === $this) {
                $connexion_logs->setUser_id(null);
            }
        }

        return $this;
    }

    public function getEmail_verification_tokenss(): Collection
    {
        return $this->email_verification_tokenss;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function getUser_2fas(): Collection
    {
        return $this->user_2fas;
    }

    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires[] = $commentaire;
            $commentaire->setId_utilisateur($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getId_utilisateur() === $this) {
                $commentaire->setId_utilisateur(null);
            }
        }

        return $this;
    }

    public function getPlanningactivites(): Collection
    {
        return $this->planningactivites;
    }

    public function getPublications(): Collection
    {
        return $this->publications;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function getTrajets(): Collection
    {
        return $this->trajets;
    }

    public function getUserIdentifier(): string
    {
        return $this->e_mail ?? '';
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        if ($this->role === 'admin') {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_unique($roles);
    }

    public function getPassword(): string
    {
        return $this->mot_de_pass ?? '';
    }

    public function eraseCredentials(): void
    {
    }
}