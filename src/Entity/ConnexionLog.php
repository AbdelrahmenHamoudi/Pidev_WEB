<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ConnexionLogRepository;

#[ORM\Entity(repositoryClass: ConnexionLogRepository::class)]
#[ORM\Table(name: 'connexion_logs')]
class ConnexionLog
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'connexionLogs')]
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

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $login_time = null;

    public function getLogin_time(): ?\DateTimeInterface
    {
        return $this->login_time;
    }

    public function setLogin_time(\DateTimeInterface $login_time): self
    {
        $this->login_time = $login_time;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $logout_time = null;

    public function getLogout_time(): ?\DateTimeInterface
    {
        return $this->logout_time;
    }

    public function setLogout_time(\DateTimeInterface $logout_time): self
    {
        $this->logout_time = $logout_time;
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $device_info = null;

    public function getDevice_info(): ?string
    {
        return $this->device_info;
    }

    public function setDevice_info(string $device_info): self
    {
        $this->device_info = $device_info;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: false)]
    private ?bool $success = null;

    public function isSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $failure_reason = null;

    public function getFailure_reason(): ?string
    {
        return $this->failure_reason;
    }

    public function setFailure_reason(string $failure_reason): self
    {
        $this->failure_reason = $failure_reason;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $country = null;

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $city = null;

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $latitude = null;

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $longitude = null;

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getLoginTime(): ?\DateTime
    {
        return $this->login_time;
    }

    public function setLoginTime(\DateTime $login_time): static
    {
        $this->login_time = $login_time;

        return $this;
    }

    public function getLogoutTime(): ?\DateTime
    {
        return $this->logout_time;
    }

    public function setLogoutTime(\DateTime $logout_time): static
    {
        $this->logout_time = $logout_time;

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

    public function getDeviceInfo(): ?string
    {
        return $this->device_info;
    }

    public function setDeviceInfo(string $device_info): static
    {
        $this->device_info = $device_info;

        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failure_reason;
    }

    public function setFailureReason(string $failure_reason): static
    {
        $this->failure_reason = $failure_reason;

        return $this;
    }

}
