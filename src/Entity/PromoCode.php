<?php

namespace App\Entity;

use App\Repository\PromoCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PromoCodeRepository::class)]
#[ORM\Table(name: 'promo_code')]
#[ORM\HasLifecycleCallbacks]
class PromoCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $promotionId = 0;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $code = '';

    /** JSON content for QR code */
    #[ORM\Column(type: 'text')]
    private string $qrContent = '';

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isUsed = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $usedBy = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $usedAt = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getPromotionId(): int { return $this->promotionId; }
    public function setPromotionId(int $v): self { $this->promotionId = $v; return $this; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $v): self { $this->code = strtoupper($v); return $this; }

    public function getQrContent(): string { return $this->qrContent; }
    public function setQrContent(string $v): self { $this->qrContent = $v; return $this; }

    public function isUsed(): bool { return $this->isUsed; }
    public function setUsed(bool $v): self { $this->isUsed = $v; return $this; }

    public function getUsedBy(): ?int { return $this->usedBy; }
    public function setUsedBy(?int $v): self { $this->usedBy = $v; return $this; }

    public function getUsedAt(): ?\DateTimeInterface { return $this->usedAt; }
    public function setUsedAt(?\DateTimeInterface $v): self { $this->usedAt = $v; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
