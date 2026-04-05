<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Users
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $nom;

    #[ORM\Column(type: "string", length: 255)]
    private string $prenom;

    #[ORM\Column(type: "string", length: 255)]
    private string $date_naiss;

    #[ORM\Column(type: "string", length: 255)]
    private string $e_mail;

    #[ORM\Column(type: "string", length: 20)]
    private string $num_tel;

    #[ORM\Column(type: "string", length: 255)]
    private string $mot_de_pass;

    #[ORM\Column(type: "string", length: 255)]
    private string $image;

    #[ORM\Column(type: "string", length: 255)]
    private string $role;

    #[ORM\Column(type: "string", length: 255)]
    private string $status;

    #[ORM\Column(type: "boolean")]
    private bool $email_verified;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getPrenom()
    {
        return $this->prenom;
    }

    public function setPrenom($value)
    {
        $this->prenom = $value;
    }

    public function getDate_naiss()
    {
        return $this->date_naiss;
    }

    public function setDate_naiss($value)
    {
        $this->date_naiss = $value;
    }

    public function getE_mail()
    {
        return $this->e_mail;
    }

    public function setE_mail($value)
    {
        $this->e_mail = $value;
    }

    public function getNum_tel()
    {
        return $this->num_tel;
    }

    public function setNum_tel($value)
    {
        $this->num_tel = $value;
    }

    public function getMot_de_pass()
    {
        return $this->mot_de_pass;
    }

    public function setMot_de_pass($value)
    {
        $this->mot_de_pass = $value;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($value)
    {
        $this->image = $value;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($value)
    {
        $this->role = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getEmail_verified()
    {
        return $this->email_verified;
    }

    public function setEmail_verified($value)
    {
        $this->email_verified = $value;
    }
}
