<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\DocteurRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DocteurRepository::class)]
class Docteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getDocteur"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getDocteur"])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getDocteur"])]
    private ?string $prenom = null;

    #[ORM\Column]
    #[Groups(["getDocteur"])]
    private ?int $telephone = null;

    /**
     * @var Collection<int, Specialite>
     */
    #[ORM\ManyToMany(targetEntity: Specialite::class, mappedBy: 'docteur')]
    #[Groups(["getDocteur"])]
    private Collection $specialites;

    public function __construct()
    {
        $this->specialites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?int
    {
        return $this->telephone;
    }

    public function setTelephone(int $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * @return Collection<int, Specialite>
     */
    public function getSpecialites(): Collection
    {
        return $this->specialites;
    }

    public function addSpecialite(Specialite $specialite): static
    {
        if (!$this->specialites->contains($specialite)) {
            $this->specialites->add($specialite);
            $specialite->addDocteur($this);
        }

        return $this;
    }

    public function removeSpecialite(Specialite $specialite): static
    {
        if ($this->specialites->removeElement($specialite)) {
            $specialite->removeDocteur($this);
        }

        return $this;
    }
}
