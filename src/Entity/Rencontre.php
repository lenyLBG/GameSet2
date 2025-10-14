<?php

namespace App\Entity;

use App\Repository\RencontreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RencontreRepository::class)]
class Rencontre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $round = null;

    #[ORM\Column]
    private ?int $points = null;

    #[ORM\ManyToOne(targetEntity: Terrains::class, inversedBy: 'rencontres')]
    private ?Terrains $terrains = null;

    #[ORM\ManyToOne(targetEntity: Equipe::class, inversedBy: 'rencontres')]
    private ?Equipe $equipes = null;

    #[ORM\ManyToOne(targetEntity: Equipe::class, inversedBy: 'rencontresVisiteur')]
    private ?Equipe $equipeVisiteur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getRound(): ?int
    {
        return $this->round;
    }

    public function setRound(?int $round): static
    {
        $this->round = $round;

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getTerrains(): ?Terrains
    {
        return $this->terrains;
    }

    public function setTerrains(?Terrains $terrains): static
    {
        $this->terrains = $terrains;

        return $this;
    }

    public function getEquipes(): ?Equipe
    {
        return $this->equipes;
    }

    public function setEquipes(?Equipe $equipes): static
    {
        $this->equipes = $equipes;

        return $this;
    }

    public function getEquipeVisiteur(): ?Equipe
    {
        return $this->equipeVisiteur;
    }

    public function setEquipeVisiteur(?Equipe $equipeVisiteur): static
    {
        $this->equipeVisiteur = $equipeVisiteur;

        return $this;
    }
}
