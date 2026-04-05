<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class User_2fa
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $user_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $secret_key;

    #[ORM\Column(type: "boolean")]
    private bool $enabled;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "text")]
    private string $backup_codes;

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

    public function getSecret_key()
    {
        return $this->secret_key;
    }

    public function setSecret_key($value)
    {
        $this->secret_key = $value;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function setEnabled($value)
    {
        $this->enabled = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    public function getBackup_codes()
    {
        return $this->backup_codes;
    }

    public function setBackup_codes($value)
    {
        $this->backup_codes = $value;
    }
}
