<?php

namespace App\Entity;

use App\Repository\RendezVousRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RendezVousRepository::class)]
class RendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $typeConsultation = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateConsultationAt = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $heureConsultation = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTypeConsultation(): ?string
    {
        return $this->typeConsultation;
    }

    public function setTypeConsultation(string $typeConsultation): static
    {
        $this->typeConsultation = $typeConsultation;

        return $this;
    }

    public function getDateConsultationAt(): ?\DateTimeImmutable
    {
        return $this->dateConsultationAt;
    }

    public function setDateConsultationAt(?\DateTimeImmutable $dateConsultationAt): static
    {
        $this->dateConsultationAt = $dateConsultationAt;

        return $this;
    }

    public function getHeureConsultation(): ?\DateTimeImmutable
    {
        return $this->heureConsultation;
    }

    public function setHeureConsultation(?\DateTimeImmutable $heureConsultation): static
    {
        $this->heureConsultation = $heureConsultation;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
