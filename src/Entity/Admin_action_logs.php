<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Users;

#[ORM\Entity]
class Admin_action_logs
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "admin_action_logss")]
    #[ORM\JoinColumn(name: 'admin_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Users $admin_id;

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

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getAdmin_id()
    {
        return $this->admin_id;
    }

    public function setAdmin_id($value)
    {
        $this->admin_id = $value;
    }

    public function getAdmin_name()
    {
        return $this->admin_name;
    }

    public function setAdmin_name($value)
    {
        $this->admin_name = $value;
    }

    public function getAction_type()
    {
        return $this->action_type;
    }

    public function setAction_type($value)
    {
        $this->action_type = $value;
    }

    public function getTarget_type()
    {
        return $this->target_type;
    }

    public function setTarget_type($value)
    {
        $this->target_type = $value;
    }

    public function getTarget_id()
    {
        return $this->target_id;
    }

    public function setTarget_id($value)
    {
        $this->target_id = $value;
    }

    public function getTarget_description()
    {
        return $this->target_description;
    }

    public function setTarget_description($value)
    {
        $this->target_description = $value;
    }

    public function getDetails()
    {
        return $this->details;
    }

    public function setDetails($value)
    {
        $this->details = $value;
    }

    public function getIp_address()
    {
        return $this->ip_address;
    }

    public function setIp_address($value)
    {
        $this->ip_address = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }
}
