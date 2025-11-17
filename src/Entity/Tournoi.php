<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\TournoiRepository;
use App\Entity\User;

#[ORM\Entity(repositoryClass: TournoiRepository::class)]
class Tournoi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $sport = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $format = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Equipe>
     */
    #[ORM\ManyToMany(targetEntity: \App\Entity\Equipe::class, mappedBy: 'tournois')]
    private \Doctrine\Common\Collections\Collection $equipes;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $creator = null;

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

    public function getSport(): ?string
    {
        return $this->sport;
    }

    public function setSport(string $sport): static
    {
        $this->sport = $sport;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function __construct()
    {
        $this->equipes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(User $user): static
    {
        $this->creator = $user;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Equipe>
     */
    public function getEquipes(): \Doctrine\Common\Collections\Collection
    {
        return $this->equipes;
    }

    public function addEquipe(\App\Entity\Equipe $equipe): static
    {
        if (!$this->equipes->contains($equipe)) {
            $this->equipes->add($equipe);
            $equipe->addTournoi($this);
        }

        return $this;
    }

    public function removeEquipe(\App\Entity\Equipe $equipe): static
    {
        if ($this->equipes->removeElement($equipe)) {
            $equipe->removeTournoi($this);
        }

        return $this;
    }
}
