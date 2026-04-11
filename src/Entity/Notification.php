<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Users;

#[ORM\Entity]
#[ORM\Table(name: 'notification')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "notifications")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    private Users $user_id;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'boolean')]
    private bool $lue = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $date_creation;

    public function __construct()
    {
        $this->date_creation = new \DateTimeImmutable();
        $this->lue = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser_id(): Users
    {
        return $this->user_id;
    }

    public function setUser_id(Users $user): self
    {
        $this->user_id = $user;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getLue(): bool
    {
        return $this->lue;
    }

    public function setLue(bool $lue): self
    {
        $this->lue = $lue;
        return $this;
    }

    public function getDate_creation(): \DateTimeImmutable
    {
        return $this->date_creation;
    }

    public function setDate_creation(\DateTimeImmutable $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }
}