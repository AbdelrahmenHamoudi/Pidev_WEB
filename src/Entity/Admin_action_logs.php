<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Users;

#[ORM\Entity]
#[ORM\Table(name: 'admin_action_logs')]
class Admin_action_logs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "admin_action_logss")]
    #[ORM\JoinColumn(name: "admin_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: true)]
    private ?Users $admin_id = null;

    #[ORM\Column(type: "string", length: 100)]
    private string $admin_name;

    #[ORM\Column(type: "string")]
    private string $action_type;

    #[ORM\Column(type: "string", length: 50)]
    private string $target_type;

    #[ORM\Column(type: "integer")]
    private int $target_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $target_description;

    #[ORM\Column(type: "text")]
    private string $details;

    #[ORM\Column(type: "string", length: 45)]
    private string $ip_address;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdmin_id(): ?Users
    {
        return $this->admin_id;
    }

    // ✅ CORRECTION 1 : ajout du ? pour accepter null
    public function setAdmin_id(?Users $admin): self
    {
        $this->admin_id = $admin;
        return $this;
    }

    public function getAdmin_name(): string
    {
        return $this->admin_name;
    }

    public function setAdmin_name(string $admin_name): self
    {
        $this->admin_name = $admin_name;
        return $this;
    }

    public function getAction_type(): string
    {
        return $this->action_type;
    }

    public function setAction_type(string $action_type): self
    {
        $this->action_type = $action_type;
        return $this;
    }

    public function getTarget_type(): string
    {
        return $this->target_type;
    }

    public function setTarget_type(string $target_type): self
    {
        $this->target_type = $target_type;
        return $this;
    }

    public function getTarget_id(): int
    {
        return $this->target_id;
    }

    public function setTarget_id(int $target_id): self
    {
        $this->target_id = $target_id;
        return $this;
    }

    public function getTarget_description(): string
    {
        return $this->target_description;
    }

    public function setTarget_description(string $target_description): self
    {
        $this->target_description = $target_description;
        return $this;
    }

    public function getDetails(): string
    {
        return $this->details;
    }

    public function setDetails(string $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getIp_address(): string
    {
        return $this->ip_address;
    }

    public function setIp_address(string $ip_address): self
    {
        $this->ip_address = $ip_address;
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
}