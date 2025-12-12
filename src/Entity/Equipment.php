<?php

namespace App\Entity;

use App\Repository\EquipmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
class Equipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Equipment_Type = null;

    #[ORM\Column(length: 255)]
    private ?string $Equipment = null;

    #[ORM\Column]
    private ?bool $Availability = null;

    #[ORM\Column]
    private ?float $Price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipmentType(): ?string
    {
        return $this->Equipment_Type;
    }

    public function setEquipmentType(string $Equipment_Type): static
    {
        $this->Equipment_Type = $Equipment_Type;

        return $this;
    }

    public function getEquipment(): ?string
    {
        return $this->Equipment;
    }

    public function setEquipment(string $Equipment): static
    {
        $this->Equipment = $Equipment;

        return $this;
    }

    public function isAvailability(): ?bool
    {
        return $this->Availability;
    }

    public function setAvailability(bool $Availability): static
    {
        $this->Availability = $Availability;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->Price;
    }

    public function setPrice(float $Price): static
    {
        $this->Price = $Price;

        return $this;
    }
}
