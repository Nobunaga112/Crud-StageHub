<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $Amount = null;

    #[ORM\Column(length: 255)]
    private ?string $Method = null;

    #[ORM\Column(length: 255)]
    private ?string $Status = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $Payment_Date = null;

    #[ORM\OneToOne(inversedBy: 'Payment', cascade: ['persist', 'remove'])]
    private ?Booking $Booking = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?string
    {
        return $this->Amount;
    }

    public function setAmount(string $Amount): static
    {
        $this->Amount = $Amount;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->Method;
    }

    public function setMethod(string $Method): static
    {
        $this->Method = $Method;

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

    public function getPaymentDate(): ?\DateTime
    {
        return $this->Payment_Date;
    }

    public function setPaymentDate(\DateTime $Payment_Date): static
    {
        $this->Payment_Date = $Payment_Date;

        return $this;
    }

    public function getBooking(): ?Booking
    {
        return $this->Booking;
    }

    public function setBooking(?Booking $Booking): static
    {
        $this->Booking = $Booking;

        return $this;
    }
}
