<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ContactMessage
{
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column]
private ?int $id = null;

#[ORM\Column(length: 255)]
private string $name;

#[ORM\Column(length: 255)]
private string $email;

#[ORM\Column(length: 255)]
private string $subject;

#[ORM\Column(length: 255)]
private string $message;

// Getters et setters
public function getId(): ?int
{
return $this->id;
}

public function setId(int $id): void
{
$this->id = $id;
}

public function getName(): string
{
return $this->name;
}

public function setName(string $name): void
{
$this->name = $name;
}

public function getEmail(): string
{
return $this->email;
}

public function setEmail(string $email): void
{
$this->email = $email;
}

public function getSubject(): string
{
return $this->subject;
}

public function setSubject(string $subject): void
{
$this->subject = $subject;
}

public function getMessage(): string
{
return $this->message;
}

public function setMessage(string $message): void
{
$this->message = $message;
}
}