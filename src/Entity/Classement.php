<?php

namespace App\Entity;

use App\Repository\ClassementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClassementRepository::class)]
class Classement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $diffButs = null;

    /**
     * @var Collection<int, tournoi>
     */
    #[ORM\OneToMany(targetEntity: tournoi::class, mappedBy: 'classement')]
    private Collection $tournois;

    public function __construct()
    {
        $this->tournois = new ArrayCollection();
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

    public function getDiffButs(): ?int
    {
        return $this->diffButs;
    }

    public function setDiffButs(?int $diffButs): static
    {
        $this->diffButs = $diffButs;

        return $this;
    }

    /**
     * @return Collection<int, tournoi>
     */
    public function getTournois(): Collection
    {
        return $this->tournois;
    }

    public function addTournoi(tournoi $tournoi): static
    {
        if (!$this->tournois->contains($tournoi)) {
            $this->tournois->add($tournoi);
            $tournoi->setClassement($this);
        }

        return $this;
    }

    public function removeTournoi(tournoi $tournoi): static
    {
        if ($this->tournois->removeElement($tournoi)) {
            // set the owning side to null (unless already changed)
            if ($tournoi->getClassement() === $this) {
                $tournoi->setClassement(null);
            }
        }

        return $this;
    }
}
