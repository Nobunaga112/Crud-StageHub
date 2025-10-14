<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?Equipment $Equipment = null;

    #[ORM\Column(length: 255)]
    private ?string $Customer_Name = null;

    #[ORM\Column(length: 255)]
    private ?string $Customer_Email = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $Start_Date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $End_Date = null;

    #[ORM\Column(length: 255)]
    private ?string $Status = null;

    #[ORM\OneToOne(mappedBy: 'Booking', cascade: ['persist', 'remove'])]
    private ?Payment $Payment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipment(): ?Equipment
    {
        return $this->Equipment;
    }

    public function setEquipment(?Equipment $Equipment): static
    {
        $this->Equipment = $Equipment;

        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->Customer_Name;
    }

    public function setCustomerName(string $Customer_Name): static
    {
        $this->Customer_Name = $Customer_Name;

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->Customer_Email;
    }

    public function setCustomerEmail(string $Customer_Email): static
    {
        $this->Customer_Email = $Customer_Email;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->Start_Date;
    }

    public function setStartDate(\DateTime $Start_Date): static
    {
        $this->Start_Date = $Start_Date;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->End_Date;
    }

    public function setEndDate(\DateTime $End_Date): static
    {
        $this->End_Date = $End_Date;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->Status;
    }

    public function setStatus(string $Status): static
    {
        $this->Status = $Status;

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->Payment;
    }

    public function setPayment(?Payment $Payment): static
    {
        // unset the owning side of the relation if necessary
        if ($Payment === null && $this->Payment !== null) {
            $this->Payment->setBooking(null);
        }

        // set the owning side of the relation if necessary
        if ($Payment !== null && $Payment->getBooking() !== $this) {
            $Payment->setBooking($this);
        }

        $this->Payment = $Payment;

        return $this;
    }
}
