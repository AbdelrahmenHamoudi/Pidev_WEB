<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Users;

#[ORM\Entity]
#[ORM\Table(name: 'connexion_logs')]
class Connexion_logs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "connexion_logss")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: true)]
    private ?Users $user_id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $login_time;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $logout_time = null;

    #[ORM\Column(type: 'string', length: 45)]
    private string $ip_address;

    #[ORM\Column(type: 'string', length: 255)]
    private string $device_info = '';

    #[ORM\Column(type: 'boolean')]
    private bool $success = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $failure_reason = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    public function __construct()
    {
        $this->login_time = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser_id(): ?Users
    {
        return $this->user_id;
    }

    public function setUser_id(?Users $user): self
    {
        $this->user_id = $user;
        return $this;
    }

    public function getLogin_time(): \DateTimeImmutable
    {
        return $this->login_time;
    }

    public function setLogin_time(\DateTimeImmutable $login_time): self
    {
        $this->login_time = $login_time;
        return $this;
    }

    public function getLogout_time(): ?\DateTimeImmutable
    {
        return $this->logout_time;
    }

    public function setLogout_time(?\DateTimeImmutable $logout_time): self
    {
        $this->logout_time = $logout_time;
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

    public function getDevice_info(): string
    {
        return $this->device_info;
    }

    public function setDevice_info(string $device_info): self
    {
        $this->device_info = $device_info;
        return $this;
    }

    public function getSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;
        return $this;
    }

    public function getFailure_reason(): ?string
    {
        return $this->failure_reason;
    }

    public function setFailure_reason(?string $failure_reason): self
    {
        $this->failure_reason = $failure_reason;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }
}