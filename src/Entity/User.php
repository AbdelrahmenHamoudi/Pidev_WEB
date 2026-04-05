<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private ?string $prenom = null;

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private ?string $date_naiss = null;

    public function getDate_naiss(): ?string
    {
        return $this->date_naiss;
    }

    public function setDate_naiss(string $date_naiss): self
    {
        $this->date_naiss = $date_naiss;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private ?string $e_mail = null;

    public function getE_mail(): ?string
    {
        return $this->e_mail;
    }

    public function setE_mail(string $e_mail): self
    {
        $this->e_mail = $e_mail;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private ?string $num_tel = null;

    public function getNum_tel(): ?string
    {
        return $this->num_tel;
    }

    public function setNum_tel(string $num_tel): self
    {
        $this->num_tel = $num_tel;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private ?string $mot_de_pass = null;

    public function getMot_de_pass(): ?string
    {
        return $this->mot_de_pass;
    }

    public function setMot_de_pass(string $mot_de_pass): self
    {
        $this->mot_de_pass = $mot_de_pass;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private ?string $image = null;

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private ?string $role = null;

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $email_verified = null;

    public function isEmail_verified(): ?bool
    {
        return $this->email_verified;
    }

    public function setEmail_verified(bool $email_verified): self
    {
        $this->email_verified = $email_verified;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: AdminActionLog::class, mappedBy: 'user')]
    private Collection $adminActionLogs;

    /**
     * @return Collection<int, AdminActionLog>
     */
    public function getAdminActionLogs(): Collection
    {
        if (!$this->adminActionLogs instanceof Collection) {
            $this->adminActionLogs = new ArrayCollection();
        }
        return $this->adminActionLogs;
    }

    public function addAdminActionLog(AdminActionLog $adminActionLog): self
    {
        if (!$this->getAdminActionLogs()->contains($adminActionLog)) {
            $this->getAdminActionLogs()->add($adminActionLog);
        }
        return $this;
    }

    public function removeAdminActionLog(AdminActionLog $adminActionLog): self
    {
        $this->getAdminActionLogs()->removeElement($adminActionLog);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'user')]
    private Collection $commentaires;

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        if (!$this->commentaires instanceof Collection) {
            $this->commentaires = new ArrayCollection();
        }
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->getCommentaires()->contains($commentaire)) {
            $this->getCommentaires()->add($commentaire);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        $this->getCommentaires()->removeElement($commentaire);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ConnexionLog::class, mappedBy: 'user')]
    private Collection $connexionLogs;

    /**
     * @return Collection<int, ConnexionLog>
     */
    public function getConnexionLogs(): Collection
    {
        if (!$this->connexionLogs instanceof Collection) {
            $this->connexionLogs = new ArrayCollection();
        }
        return $this->connexionLogs;
    }

    public function addConnexionLog(ConnexionLog $connexionLog): self
    {
        if (!$this->getConnexionLogs()->contains($connexionLog)) {
            $this->getConnexionLogs()->add($connexionLog);
        }
        return $this;
    }

    public function removeConnexionLog(ConnexionLog $connexionLog): self
    {
        $this->getConnexionLogs()->removeElement($connexionLog);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: EmailVerificationToken::class, mappedBy: 'user')]
    private Collection $emailVerificationTokens;

    /**
     * @return Collection<int, EmailVerificationToken>
     */
    public function getEmailVerificationTokens(): Collection
    {
        if (!$this->emailVerificationTokens instanceof Collection) {
            $this->emailVerificationTokens = new ArrayCollection();
        }
        return $this->emailVerificationTokens;
    }

    public function addEmailVerificationToken(EmailVerificationToken $emailVerificationToken): self
    {
        if (!$this->getEmailVerificationTokens()->contains($emailVerificationToken)) {
            $this->getEmailVerificationTokens()->add($emailVerificationToken);
        }
        return $this;
    }

    public function removeEmailVerificationToken(EmailVerificationToken $emailVerificationToken): self
    {
        $this->getEmailVerificationTokens()->removeElement($emailVerificationToken);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private Collection $notifications;

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        if (!$this->notifications instanceof Collection) {
            $this->notifications = new ArrayCollection();
        }
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->getNotifications()->contains($notification)) {
            $this->getNotifications()->add($notification);
        }
        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        $this->getNotifications()->removeElement($notification);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Planningactivite::class, mappedBy: 'user')]
    private Collection $planningactivites;

    /**
     * @return Collection<int, Planningactivite>
     */
    public function getPlanningactivites(): Collection
    {
        if (!$this->planningactivites instanceof Collection) {
            $this->planningactivites = new ArrayCollection();
        }
        return $this->planningactivites;
    }

    public function addPlanningactivite(Planningactivite $planningactivite): self
    {
        if (!$this->getPlanningactivites()->contains($planningactivite)) {
            $this->getPlanningactivites()->add($planningactivite);
        }
        return $this;
    }

    public function removePlanningactivite(Planningactivite $planningactivite): self
    {
        $this->getPlanningactivites()->removeElement($planningactivite);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Publication::class, mappedBy: 'user')]
    private Collection $publications;

    /**
     * @return Collection<int, Publication>
     */
    public function getPublications(): Collection
    {
        if (!$this->publications instanceof Collection) {
            $this->publications = new ArrayCollection();
        }
        return $this->publications;
    }

    public function addPublication(Publication $publication): self
    {
        if (!$this->getPublications()->contains($publication)) {
            $this->getPublications()->add($publication);
        }
        return $this;
    }

    public function removePublication(Publication $publication): self
    {
        $this->getPublications()->removeElement($publication);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'user')]
    private Collection $reservations;

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        if (!$this->reservations instanceof Collection) {
            $this->reservations = new ArrayCollection();
        }
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->getReservations()->contains($reservation)) {
            $this->getReservations()->add($reservation);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        $this->getReservations()->removeElement($reservation);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Trajet::class, mappedBy: 'user')]
    private Collection $trajets;

    /**
     * @return Collection<int, Trajet>
     */
    public function getTrajets(): Collection
    {
        if (!$this->trajets instanceof Collection) {
            $this->trajets = new ArrayCollection();
        }
        return $this->trajets;
    }

    public function addTrajet(Trajet $trajet): self
    {
        if (!$this->getTrajets()->contains($trajet)) {
            $this->getTrajets()->add($trajet);
        }
        return $this;
    }

    public function removeTrajet(Trajet $trajet): self
    {
        $this->getTrajets()->removeElement($trajet);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: User2fa::class, mappedBy: 'user')]
    private Collection $user2fas;

    public function __construct()
    {
        $this->adminActionLogs = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->connexionLogs = new ArrayCollection();
        $this->emailVerificationTokens = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->planningactivites = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->trajets = new ArrayCollection();
        $this->user2fas = new ArrayCollection();
    }

    /**
     * @return Collection<int, User2fa>
     */
    public function getUser2fas(): Collection
    {
        if (!$this->user2fas instanceof Collection) {
            $this->user2fas = new ArrayCollection();
        }
        return $this->user2fas;
    }

    public function addUser2fa(User2fa $user2fa): self
    {
        if (!$this->getUser2fas()->contains($user2fa)) {
            $this->getUser2fas()->add($user2fa);
        }
        return $this;
    }

    public function removeUser2fa(User2fa $user2fa): self
    {
        $this->getUser2fas()->removeElement($user2fa);
        return $this;
    }

    public function getDateNaiss(): ?string
    {
        return $this->date_naiss;
    }

    public function setDateNaiss(string $date_naiss): static
    {
        $this->date_naiss = $date_naiss;

        return $this;
    }

    public function getEMail(): ?string
    {
        return $this->e_mail;
    }

    public function setEMail(string $e_mail): static
    {
        $this->e_mail = $e_mail;

        return $this;
    }

    public function getNumTel(): ?string
    {
        return $this->num_tel;
    }

    public function setNumTel(string $num_tel): static
    {
        $this->num_tel = $num_tel;

        return $this;
    }

    public function getMotDePass(): ?string
    {
        return $this->mot_de_pass;
    }

    public function setMotDePass(string $mot_de_pass): static
    {
        $this->mot_de_pass = $mot_de_pass;

        return $this;
    }

    public function isEmailVerified(): ?bool
    {
        return $this->email_verified;
    }

    public function setEmailVerified(bool $email_verified): static
    {
        $this->email_verified = $email_verified;

        return $this;
    }

}
