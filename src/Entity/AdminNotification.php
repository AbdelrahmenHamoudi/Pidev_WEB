<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\AdminNotificationRepository;

#[ORM\Entity(repositoryClass: AdminNotificationRepository::class)]
#[ORM\Table(name: 'admin_notifications')]
class AdminNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $title = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $message = null;

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $priority = null;

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $is_read = null;

    public function is_read(): ?bool
    {
        return $this->is_read;
    }

    public function setIs_read(bool $is_read): self
    {
        $this->is_read = $is_read;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $expires_at = null;

    public function getExpires_at(): ?\DateTimeInterface
    {
        return $this->expires_at;
    }

    public function setExpires_at(\DateTimeInterface $expires_at): self
    {
        $this->expires_at = $expires_at;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $action_link = null;

    public function getAction_link(): ?string
    {
        return $this->action_link;
    }

    public function setAction_link(string $action_link): self
    {
        $this->action_link = $action_link;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $action_text = null;

    public function getAction_text(): ?string
    {
        return $this->action_text;
    }

    public function setAction_text(string $action_text): self
    {
        $this->action_text = $action_text;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $created_by = null;

    public function getCreated_by(): ?string
    {
        return $this->created_by;
    }

    public function setCreated_by(string $created_by): self
    {
        $this->created_by = $created_by;
        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->is_read;
    }

    public function setIsRead(bool $is_read): static
    {
        $this->is_read = $is_read;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expires_at;
    }

    public function setExpiresAt(\DateTime $expires_at): static
    {
        $this->expires_at = $expires_at;

        return $this;
    }

    public function getActionLink(): ?string
    {
        return $this->action_link;
    }

    public function setActionLink(string $action_link): static
    {
        $this->action_link = $action_link;

        return $this;
    }

    public function getActionText(): ?string
    {
        return $this->action_text;
    }

    public function setActionText(string $action_text): static
    {
        $this->action_text = $action_text;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->created_by;
    }

    public function setCreatedBy(string $created_by): static
    {
        $this->created_by = $created_by;

        return $this;
    }

}
