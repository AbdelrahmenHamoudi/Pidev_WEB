<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Users;

#[ORM\Entity]
class Connexion_logs
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "connexion_logss")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Users $user_id;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $login_time;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $logout_time;

    #[ORM\Column(type: "string", length: 45)]
    private string $ip_address;

    #[ORM\Column(type: "string", length: 255)]
    private string $device_info;

    #[ORM\Column(type: "boolean")]
    private bool $success;

    #[ORM\Column(type: "string", length: 255)]
    private string $failure_reason;

    #[ORM\Column(type: "string", length: 100)]
    private string $country;

    #[ORM\Column(type: "string", length: 100)]
    private string $city;

    #[ORM\Column(type: "float")]
    private float $latitude;

    #[ORM\Column(type: "float")]
    private float $longitude;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getLogin_time()
    {
        return $this->login_time;
    }

    public function setLogin_time($value)
    {
        $this->login_time = $value;
    }

    public function getLogout_time()
    {
        return $this->logout_time;
    }

    public function setLogout_time($value)
    {
        $this->logout_time = $value;
    }

    public function getIp_address()
    {
        return $this->ip_address;
    }

    public function setIp_address($value)
    {
        $this->ip_address = $value;
    }

    public function getDevice_info()
    {
        return $this->device_info;
    }

    public function setDevice_info($value)
    {
        $this->device_info = $value;
    }

    public function getSuccess()
    {
        return $this->success;
    }

    public function setSuccess($value)
    {
        $this->success = $value;
    }

    public function getFailure_reason()
    {
        return $this->failure_reason;
    }

    public function setFailure_reason($value)
    {
        $this->failure_reason = $value;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($value)
    {
        $this->country = $value;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity($value)
    {
        $this->city = $value;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLatitude($value)
    {
        $this->latitude = $value;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function setLongitude($value)
    {
        $this->longitude = $value;
    }
}
