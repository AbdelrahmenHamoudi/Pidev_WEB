<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\AdminActionLogRepository;

#[ORM\Entity(repositoryClass: AdminActionLogRepository::class)]
#[ORM\Table(name: 'admin_action_logs')]
class AdminActionLog
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'adminActionLogs')]
    #[ORM\JoinColumn(name: 'admin_id', referencedColumnName: 'id')]
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $admin_name = null;

    public function getAdmin_name(): ?string
    {
        return $this->admin_name;
    }

    public function setAdmin_name(string $admin_name): self
    {
        $this->admin_name = $admin_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $action_type = null;

    public function getAction_type(): ?string
    {
        return $this->action_type;
    }

    public function setAction_type(string $action_type): self
    {
        $this->action_type = $action_type;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $target_type = null;

    public function getTarget_type(): ?string
    {
        return $this->target_type;
    }

    public function setTarget_type(string $target_type): self
    {
        $this->target_type = $target_type;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $target_id = null;

    public function getTarget_id(): ?int
    {
        return $this->target_id;
    }

    public function setTarget_id(int $target_id): self
    {
        $this->target_id = $target_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $target_description = null;

    public function getTarget_description(): ?string
    {
        return $this->target_description;
    }

    public function setTarget_description(string $target_description): self
    {
        $this->target_description = $target_description;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $details = null;

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): self
    {
        $this->details = $details;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $ip_address = null;

    public function getIp_address(): ?string
    {
        return $this->ip_address;
    }

    public function setIp_address(string $ip_address): self
    {
        $this->ip_address = $ip_address;
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

    public function getAdminName(): ?string
    {
        return $this->admin_name;
    }

    public function setAdminName(string $admin_name): static
    {
        $this->admin_name = $admin_name;

        return $this;
    }

    public function getActionType(): ?string
    {
        return $this->action_type;
    }

    public function setActionType(string $action_type): static
    {
        $this->action_type = $action_type;

        return $this;
    }

    public function getTargetType(): ?string
    {
        return $this->target_type;
    }

    public function setTargetType(string $target_type): static
    {
        $this->target_type = $target_type;

        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->target_id;
    }

    public function setTargetId(int $target_id): static
    {
        $this->target_id = $target_id;

        return $this;
    }

    public function getTargetDescription(): ?string
    {
        return $this->target_description;
    }

    public function setTargetDescription(string $target_description): static
    {
        $this->target_description = $target_description;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

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

}
