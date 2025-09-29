<?php

namespace App\Entity;

use App\Repository\TerrainsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TerrainsRepository::class)]
class Terrains
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    /**
     * @var Collection<int, rencontre>
     */
    #[ORM\OneToMany(targetEntity: rencontre::class, mappedBy: 'terrains')]
    private Collection $rencontres;

    public function __construct()
    {
        $this->rencontres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
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

    /**
     * @return Collection<int, rencontre>
     */
    public function getRencontres(): Collection
    {
        return $this->rencontres;
    }

    public function addRencontre(rencontre $rencontre): static
    {
        if (!$this->rencontres->contains($rencontre)) {
            $this->rencontres->add($rencontre);
            $rencontre->setTerrains($this);
        }

        return $this;
    }

    public function removeRencontre(rencontre $rencontre): static
    {
        if ($this->rencontres->removeElement($rencontre)) {
            // set the owning side to null (unless already changed)
            if ($rencontre->getTerrains() === $this) {
                $rencontre->setTerrains(null);
            }
        }

        return $this;
    }
}
