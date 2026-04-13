<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Users;

#[ORM\Entity]
class User_2fa
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "user_2fas")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Users $user_id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $secret_key;

    #[ORM\Column(type: "boolean")]
    private bool $enabled = false;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: "text")]
    private string $backup_codes = '[]';

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser_id(): ?Users
    {
        return $this->user_id;
    }

    public function setUser_id(?Users $value): self
    {
        $this->user_id = $value;
        return $this;
    }

    public function getSecret_key(): string
    {
        return $this->secret_key;
    }

    public function setSecret_key(string $value): self
    {
        $this->secret_key = $value;
        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $value): self
    {
        $this->enabled = $value;
        return $this;
    }

    public function getCreated_at(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeImmutable $value): self
    {
        $this->created_at = $value;
        return $this;
    }

    public function getBackup_codes(): string
    {
        return $this->backup_codes;
    }

    public function setBackup_codes(string $value): self
    {
        $this->backup_codes = $value;
        return $this;
    }

    public function getBackupCodesArray(): array
    {
        return json_decode($this->backup_codes, true) ?: [];
    }

    public function setBackupCodesArray(array $codes): self
    {
        $this->backup_codes = json_encode($codes);
        return $this;
    }

    public function useBackupCode(string $code): bool
    {
        $codes = $this->getBackupCodesArray();
        $index = array_search($code, $codes, true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $this->setBackupCodesArray(array_values($codes));
        return true;
    }
}