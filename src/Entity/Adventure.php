<?php

namespace App\Entity;

use App\Enum\Status;
use App\Enum\ViewAuthorization;
use App\Repository\AdventureRepository;
use App\Entity\ContactList;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdventureRepository::class)]
class Adventure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    #[Assert\LessThanOrEqual(propertyPath: 'endDate', message: 'La date de début doit précéder la date de fin.')]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(propertyPath: 'startDate', message: 'La date de fin doit être après la date de début.')]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(enumType: Status::class)]
    #[Assert\NotNull(message: 'Le statut est requis.')]
    private Status $status;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mapGpx = null;

    #[ORM\Column(enumType: ViewAuthorization::class)]
    private ViewAuthorization $viewAuthorization;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $shareLink = null;

    /**
     * @var Collection<int, AdventureType>
     */
    #[ORM\ManyToMany(targetEntity: AdventureType::class, inversedBy: 'adventures')]
    private Collection $types;

    /**
     * @var Collection<int, AdventurePoint>
     */
    #[ORM\OneToMany(targetEntity: AdventurePoint::class, mappedBy: 'adventure', orphanRemoval: true)]
    private Collection $adventurePoints;

    /**
     * @var Collection<int, AdventureFile>
     */
    #[ORM\OneToMany(targetEntity: AdventureFile::class, mappedBy: 'adventure', orphanRemoval: true)]
    private Collection $adventureFiles;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: "authorizedAdventures")]
    private Collection $authorizedUsers;


    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\OneToOne(mappedBy: 'adventure', targetEntity: TimerAlert::class, cascade: ['persist', 'remove'])]
    private ?TimerAlert $timerAlert = null;

    /**
     * @var Collection<int, SafetyAlert>
     */
    #[ORM\OneToMany(mappedBy: 'adventure', targetEntity: SafetyAlert::class, orphanRemoval: true)]
    private Collection $safetyAlerts;

    /**
     * @var Collection<int, AdventurePicture>
     */

    #[ORM\OneToMany(mappedBy: 'adventure', targetEntity: AdventurePicture::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $pictures;

    #[ORM\ManyToOne(targetEntity: ContactList::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ContactList $contactList = null;

    #[ORM\PrePersist]
    public function generateShareLink(): void
    {
        $this->shareLink = bin2hex(random_bytes(16)); // ou utilise Uuid
    }

    public function __construct()
    {
        $this->types = new ArrayCollection();
        $this->adventurePoints = new ArrayCollection();
        $this->adventureFiles = new ArrayCollection();
        $this->authorizedUsers = new ArrayCollection();
        $this->safetyAlerts = new ArrayCollection();

        $this->createdAt = new \DateTimeImmutable();
        $this->pictures = new ArrayCollection(); 
    }

    public function isVisibleTo(?User $user): bool {
        if ($this->viewAuthorization === ViewAuthorization::PUBLIC) {
            return true;
        }

        if (!$user) {
            return false; // accès impossible si non connecté
        }

        // Le propriétaire peut toujours voir
        if ($this->getOwner() === $user) {
            return true;
        }

        // Si visibilité par sélection, vérifier si l’utilisateur est autorisé
        if ($this->viewAuthorization === ViewAuthorization::SELECTION) {
            return $this->authorizedUsers->contains($user);
        }

        return false; // PRIVATE
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getMapGpx(): ?string
    {
        return $this->mapGpx;
    }

    public function setMapGpx(?string $mapGpx): static
    {
        $this->mapGpx = $mapGpx;

        return $this;
    }

    public function getViewAuthorization(): ViewAuthorization
    {
        return $this->viewAuthorization;
    }
    
    public function setViewAuthorization(ViewAuthorization $viewAuthorization): static
    {
        $this->viewAuthorization = $viewAuthorization;
        return $this;
    }

    public function getShareLink(): ?string
    {
        return $this->shareLink;
    }

    public function setShareLink(string $shareLink): static
    {
        $this->shareLink = $shareLink;

        return $this;
    }

    /**
     * @return Collection<int, AdventureType>
     */
    public function getTypes(): Collection
    {
        return $this->types;
    }

    public function addType(AdventureType $type): static
    {
        if (!$this->types->contains($type)) {
            $this->types->add($type);
        }

        return $this;
    }

    public function removeType(AdventureType $type): static
    {
        $this->types->removeElement($type);

        return $this;
    }

    /**
     * @return Collection<int, AdventurePoint>
     */
    public function getAdventurePoints(): Collection
    {
        return $this->adventurePoints;
    }

    public function addAdventurePoint(AdventurePoint $adventurePoint): static
    {
        if (!$this->adventurePoints->contains($adventurePoint)) {
            $this->adventurePoints->add($adventurePoint);
            $adventurePoint->setAdventure($this);
        }

        return $this;
    }

    public function removeAdventurePoint(AdventurePoint $adventurePoint): static
    {
        if ($this->adventurePoints->removeElement($adventurePoint)) {
            // set the owning side to null (unless already changed)
            if ($adventurePoint->getAdventure() === $this) {
                $adventurePoint->setAdventure(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AdventureFile>
     */
    public function getAdventureFiles(): Collection
    {
        return $this->adventureFiles;
    }

    public function addAdventureFile(AdventureFile $adventureFile): static
    {
        if (!$this->adventureFiles->contains($adventureFile)) {
            $this->adventureFiles->add($adventureFile);
            $adventureFile->setAdventure($this);
        }

        return $this;
    }

    public function removeAdventureFile(AdventureFile $adventureFile): static
    {
        if ($this->adventureFiles->removeElement($adventureFile)) {
            // set the owning side to null (unless already changed)
            if ($adventureFile->getAdventure() === $this) {
                $adventureFile->setAdventure(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAuthorizedUsers(): Collection
    {
        return $this->authorizedUsers;
    }

    public function addAuthorizedUser(User $authorizedUser): static
    {
        if (!$this->authorizedUsers->contains($authorizedUser)) {
            $this->authorizedUsers->add($authorizedUser);
        }

        return $this;
    }

    public function removeAuthorizedUser(User $authorizedUser): static
    {
        $this->authorizedUsers->removeElement($authorizedUser);

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, SafetyAlert>
     */
    public function getSafetyAlerts(): Collection
    {
        return $this->safetyAlerts;
    }

    public function addSafetyAlert(SafetyAlert $safetyAlert): static
    {
        if (!$this->safetyAlerts->contains($safetyAlert)) {
            $this->safetyAlerts->add($safetyAlert);
            $safetyAlert->setAdventure($this);
        }

        return $this;
    }

    public function removeSafetyAlert(SafetyAlert $safetyAlert): static
    {
        if ($this->safetyAlerts->removeElement($safetyAlert)) {
            // set the owning side to null (unless already changed)
            if ($safetyAlert->getAdventure() === $this) {
                $safetyAlert->setAdventure(null);
            }
        }

        return $this;
    }

    public function getTimerAlert(): ?TimerAlert
    {
        return $this->timerAlert;
    }

    public function setTimerAlert(?TimerAlert $timerAlert): self
    {
        $this->timerAlert = $timerAlert;

        if ($timerAlert !== null && $timerAlert->getAdventure() !== $this) {
            $timerAlert->setAdventure($this);
        }

        return $this;
    }


    public function hasActiveTimer(): bool
    {
        return $this->timerAlert !== null && $this->timerAlert->isActive();
    }

    /**
     * @return Collection<int, AdventurePicture>
     */
    public function getPictures(): Collection
    {
        return $this->pictures;
    }

    public function addPicture(AdventurePicture $picture): static
    {
        if (!$this->pictures->contains($picture)) {
            $this->pictures->add($picture);
            $picture->setAdventure($this);
        }

        return $this;
    }

    public function removePicture(AdventurePicture $picture): static
    {
        if ($this->pictures->removeElement($picture)) {
            // set the owning side to null (unless already changed)
            if ($picture->getAdventure() === $this) {
                $picture->setAdventure(null);
            }
        }

        return $this;
    }

    public function getContactList(): ?ContactList
    {
        return $this->contactList;
    }

    public function setContactList(?ContactList $contactList): static
    {
        $this->contactList = $contactList;
        return $this;
    }
}
