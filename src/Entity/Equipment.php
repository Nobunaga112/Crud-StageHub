<?php

namespace App\Entity;

use App\Repository\EquipmentRepository;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $Rent_Date = null;

    #[ORM\Column]
    private ?float $Payment = null;

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

    public function getRentDate(): ?\DateTime
    {
        return $this->Rent_Date;
    }

    public function setRentDate(\DateTime $Rent_Date): static
    {
        $this->Rent_Date = $Rent_Date;

        return $this;
    }

    public function getPayment(): ?float
    {
        return $this->Payment;
    }

    public function setPayment(float $Payment): static
    {
        $this->Payment = $Payment;

        return $this;
    }
}
