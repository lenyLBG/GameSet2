<?php

namespace App\Entity;

use App\Repository\RencontreRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Tournoi;
use App\Entity\Equipe;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: RencontreRepository::class)]
class Rencontre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $round = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column(nullable: true)]
    private ?int $points = null;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $status = 'pending';

    #[ORM\Column(type: Types::STRING, length: 16)]
    private string $bracket = 'winners';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $score_home = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $score_away = null;

    #[ORM\ManyToOne(targetEntity: Terrains::class, inversedBy: 'rencontres')]
    private ?Terrains $terrains = null;

    #[ORM\ManyToOne(targetEntity: Equipe::class, inversedBy: 'rencontres')]
    private ?Equipe $equipes = null;

    #[ORM\ManyToOne(targetEntity: Equipe::class, inversedBy: 'rencontresVisiteur')]
    private ?Equipe $equipeVisiteur = null;

    #[ORM\ManyToOne(targetEntity: Tournoi::class, inversedBy: 'rencontres')]
    private ?Tournoi $tournoi = null;

    #[ORM\ManyToOne(targetEntity: Equipe::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Equipe $winner = null;

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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $pos): static
    {
        $this->position = $pos;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getBracket(): string
    {
        return $this->bracket;
    }

    public function setBracket(string $br): static
    {
        $this->bracket = $br;

        return $this;
    }

    public function getScoreHome(): ?int
    {
        return $this->score_home;
    }

    public function setScoreHome(?int $score): static
    {
        $this->score_home = $score;

        return $this;
    }

    public function getScoreAway(): ?int
    {
        return $this->score_away;
    }

    public function setScoreAway(?int $score): static
    {
        $this->score_away = $score;

        return $this;
    }

    public function getTournoi(): ?Tournoi
    {
        return $this->tournoi;
    }

    public function setTournoi(?Tournoi $t): static
    {
        $this->tournoi = $t;
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

    public function getWinner(): ?Equipe
    {
        return $this->winner;
    }

    public function setWinner(?Equipe $winner): static
    {
        $this->winner = $winner;

        return $this;
    }
}
