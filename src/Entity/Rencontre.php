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

    #[ORM\ManyToOne(inversedBy: 'rencontres')]
    private ?Terrains $terrains = null;

    #[ORM\ManyToOne(inversedBy: 'rencontres')]
    private ?equipe $equipes = null;

    #[ORM\ManyToOne(inversedBy: 'rencontres')]
    private ?equipe $equipeVisiteur = null;

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

    public function getEquipes(): ?equipe
    {
        return $this->equipes;
    }

    public function setEquipes(?equipe $equipes): static
    {
        $this->equipes = $equipes;

        return $this;
    }

    public function getEquipeVisiteur(): ?equipe
    {
        return $this->equipeVisiteur;
    }

    public function setEquipeVisiteur(?equipe $equipeVisiteur): static
    {
        $this->equipeVisiteur = $equipeVisiteur;

        return $this;
    }
}
