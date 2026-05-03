<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'admin_notifications')]
class Admin_notifications
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 50)]
    private string $priority;

    #[ORM\Column(type: 'boolean')]
    private bool $is_read = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expires_at = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $action_link = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $action_text = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $created_by;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->is_read = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getIs_read(): bool
    {
        return $this->is_read;
    }

    public function setIs_read(bool $is_read): self
    {
        $this->is_read = $is_read;
        return $this;
    }

    public function getCreated_at(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeImmutable $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getExpires_at(): ?\DateTimeImmutable
    {
        return $this->expires_at;
    }

    public function setExpires_at(?\DateTimeImmutable $expires_at): self
    {
        $this->expires_at = $expires_at;
        return $this;
    }

    public function getAction_link(): ?string
    {
        return $this->action_link;
    }

    public function setAction_link(?string $action_link): self
    {
        $this->action_link = $action_link;
        return $this;
    }

    public function getAction_text(): ?string
    {
        return $this->action_text;
    }

    public function setAction_text(?string $action_text): self
    {
        $this->action_text = $action_text;
        return $this;
    }

    public function getCreated_by(): string
    {
        return $this->created_by;
    }

    public function setCreated_by(string $created_by): self
    {
        $this->created_by = $created_by;
        return $this;
    }
}