<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\User2faRepository;

#[ORM\Entity(repositoryClass: User2faRepository::class)]
#[ORM\Table(name: 'user_2fa')]
class User2fa
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'user2fas')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
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
    private ?string $secret_key = null;

    public function getSecret_key(): ?string
    {
        return $this->secret_key;
    }

    public function setSecret_key(string $secret_key): self
    {
        $this->secret_key = $secret_key;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $enabled = null;

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
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

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $backup_codes = null;

    public function getBackup_codes(): ?string
    {
        return $this->backup_codes;
    }

    public function setBackup_codes(string $backup_codes): self
    {
        $this->backup_codes = $backup_codes;
        return $this;
    }

    public function getSecretKey(): ?string
    {
        return $this->secret_key;
    }

    public function setSecretKey(string $secret_key): static
    {
        $this->secret_key = $secret_key;

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

    public function getBackupCodes(): ?string
    {
        return $this->backup_codes;
    }

    public function setBackupCodes(string $backup_codes): static
    {
        $this->backup_codes = $backup_codes;

        return $this;
    }

}
