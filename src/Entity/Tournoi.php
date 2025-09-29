<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\TournoiRepository;

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
}
