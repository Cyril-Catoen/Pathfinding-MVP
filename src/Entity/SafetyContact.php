<?php

namespace App\Entity;

use App\Repository\SafetyContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SafetyContactRepository::class)]
class SafetyContact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 100)]
    private ?string $country = null;

    #[ORM\Column]
    private ?bool $declarationOfMajority = null;

    #[ORM\Column]
    private ?bool $isVerified = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\ManyToOne(inversedBy: 'safetyContacts')]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, SafetyAlert>
     */
    #[ORM\ManyToMany(targetEntity: SafetyAlert::class, mappedBy: 'notifiedContacts')]
    private Collection $safetyAlerts;

    public function __construct()
    {
        $this->safetyAlerts = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function regenerateVerificationToken(): void
    {
        $this->verificationToken = bin2hex(random_bytes(16));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function isDeclarationOfMajority(): ?bool
    {
        return $this->declarationOfMajority;
    }

    public function setDeclarationOfMajority(bool $declarationOfMajority): static
    {
        $this->declarationOfMajority = $declarationOfMajority;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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
            $safetyAlert->addNotifiedContact($this);
        }

        return $this;
    }

    public function removeSafetyAlert(SafetyAlert $safetyAlert): static
    {
        if ($this->safetyAlerts->removeElement($safetyAlert)) {
            $safetyAlert->removeNotifiedContact($this);
        }

        return $this;
    }
}
