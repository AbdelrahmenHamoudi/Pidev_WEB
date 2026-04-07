<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Users;

#[ORM\Entity]
class Email_verification_tokens
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "email_verification_tokenss")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Users $user_id;

    #[ORM\Column(type: "string", length: 10)]
    private string $code;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $expires_at;

    #[ORM\Column(type: "boolean")]
    private bool $verified;

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

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($value)
    {
        $this->code = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    public function getExpires_at()
    {
        return $this->expires_at;
    }

    public function setExpires_at($value)
    {
        $this->expires_at = $value;
    }

    public function getVerified()
    {
        return $this->verified;
    }

    public function setVerified($value)
    {
        $this->verified = $value;
    }
}
