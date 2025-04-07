<?php

namespace App\Entity;

use App\Repository\RendezVousRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: RendezVousRepository::class)]
class RendezVous
{
    const STATUT_ENATTENTE = "En attente";
    const STATUT_ACCEPTE = "Accepté";
    const STATUT_REFUSE = "Refusé";
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getRendezVous", "getDocteur", "getPatient"])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getRendezVous", "getDocteur", "getPatient"])]
    private ?string $typeConsultation = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["getRendezVous", "getDocteur", "getPatient"])]
    private ?\DateTimeImmutable $dateConsultationAt = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    #[Groups(["getRendezVous", "getDocteur", "getPatient"])]
    private ?\DateTimeImmutable $heureConsultation = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getRendezVous", "getDocteur", "getPatient"])]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'rendezVouses')]
    #[Groups(["getRendezVous"])]
    private ?Docteur $docteur = null;

    #[ORM\ManyToOne(inversedBy: 'rendezVouses')]
    #[Ignore]
    private ?Patient $patient = null;

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

    public function getDocteur(): ?Docteur
    {
        return $this->docteur;
    }

    public function setDocteur(?Docteur $docteur): static
    {
        $this->docteur = $docteur;

        return $this;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        $this->patient = $patient;

        return $this;
    }
}
