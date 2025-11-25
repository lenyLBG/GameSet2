<?php

namespace App\Entity;

use App\Repository\EquipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipeRepository::class)]
class Equipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coach = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sport = null;

    /**
     * @var Collection<int, Rencontre>
     */
    #[ORM\OneToMany(targetEntity: Rencontre::class, mappedBy: 'equipes')]
    private Collection $rencontres;

    /**
     * @var Collection<int, Rencontre>
     */
    #[ORM\OneToMany(targetEntity: Rencontre::class, mappedBy: 'equipeVisiteur')]
    private Collection $rencontresVisiteur;

    /**
     * @var Collection<int, tournoi>
     */
    /**
     * @var Collection<int, Tournoi>
     */
    #[ORM\ManyToMany(targetEntity: Tournoi::class, inversedBy: 'equipes')]
    private Collection $tournois;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'equipe')]
    private Collection $users;

    /**
     * @var array
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $manualParticipants = [];

    public function __construct()
    {
        $this->rencontres = new ArrayCollection();
    $this->rencontresVisiteur = new ArrayCollection();
        $this->tournois = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->manualParticipants = [];
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

    public function getCoach(): ?string
    {
        return $this->coach;
    }

    public function setCoach(?string $coach): static
    {
        $this->coach = $coach;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getSport(): ?string
    {
        return $this->sport;
    }

    public function setSport(?string $sport): static
    {
        $this->sport = $sport;

        return $this;
    }

    /**
     * @return Collection<int, Rencontre>
     */
    public function getRencontres(): Collection
    {
        return $this->rencontres;
    }

    public function addRencontre(Rencontre $rencontre): static
    {
        if (!$this->rencontres->contains($rencontre)) {
            $this->rencontres->add($rencontre);
            $rencontre->setEquipes($this);
        }

        return $this;
    }

    public function removeRencontre(Rencontre $rencontre): static
    {
        if ($this->rencontres->removeElement($rencontre)) {
            // set the owning side to null (unless already changed)
            if ($rencontre->getEquipes() === $this) {
                $rencontre->setEquipes(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Rencontre>
     */
    public function getRencontresVisiteur(): Collection
    {
        return $this->rencontresVisiteur;
    }

    /**
     * @return Collection<int, tournoi>
     */
    public function getTournois(): Collection
    {
        return $this->tournois;
    }

    public function addTournoi(Tournoi $tournoi): static
    {
        if (!$this->tournois->contains($tournoi)) {
            $this->tournois->add($tournoi);
        }

        return $this;
    }

    public function removeTournoi(Tournoi $tournoi): static
    {
        $this->tournois->removeElement($tournoi);

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addEquipe($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeEquipe($this);
        }

        return $this;
    }

    public function getManualParticipants(): array
    {
        // Always return an array, never null
        if (!is_array($this->manualParticipants)) {
            $this->manualParticipants = [];
        }
        return $this->manualParticipants;
    }

    public function setManualParticipants(?array $manualParticipants): static
    {
        // Ensure we always have an array, not null
        $this->manualParticipants = is_array($manualParticipants) ? $manualParticipants : [];

        return $this;
    }

    public function addManualParticipant(string $name): static
    {
        // Ensure manualParticipants is always an array
        if (!is_array($this->manualParticipants)) {
            $this->manualParticipants = [];
        }
        
        if (!in_array($name, $this->manualParticipants)) {
            $this->manualParticipants[] = $name;
        }

        return $this;
    }

    public function removeManualParticipant(string $name): static
    {
        // Ensure manualParticipants is always an array
        if (!is_array($this->manualParticipants)) {
            $this->manualParticipants = [];
        } else {
            $this->manualParticipants = array_filter($this->manualParticipants, fn($p) => $p !== $name);
        }

        return $this;
    }
}
