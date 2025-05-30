<?php

namespace App\Entity;

use App\Entity\ContactList; 
use Symfony\Component\Validator\Constraints as Assert;
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

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\p{L}+$/u',
        message: 'Only letters are allowed'
    )]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\p{L}+$/u',
        message: 'Only letters are allowed'
    )]
    private ?string $lastName = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\+[\d\s\-()]{10,}$/',
        message: 'Phone must start with + and contain only digits after. Allowed: spaces, hyphens, parentheses.'
    )]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 2)]
    private ?string $country = null;

    #[ORM\Column]
    #[Assert\IsTrue(message: 'You must declare your contact is legally an adult.')]
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

    #[ORM\Column(options: ['default' => false])]
    private bool $isFavorite = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $picturePath = null;

    #[ORM\ManyToMany(targetEntity: ContactList::class, inversedBy: 'contacts')]
    private Collection $contactLists;

    /**
     * @var Collection<int, SafetyAlert>
     */
    #[ORM\ManyToMany(targetEntity: SafetyAlert::class, mappedBy: 'notifiedContacts')]
    private Collection $safetyAlerts;

    public function __construct()
    {
        $this->safetyAlerts = new ArrayCollection();
        $this->contactLists = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function createSafetyContact(
        string $email,
        string $firstName,
        string $lastName,
        string $phoneNumber,
        string $country,
        ?string $picturePath,
        bool $declarationOfMajority,
        User $owner,
        ?array $contactLists = []
    ): void {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phoneNumber = $phoneNumber;
        $this->country = $country;
        $this->picturePath = $picturePath;
        $this->declarationOfMajority = $declarationOfMajority;

        $this->user = $owner;
        $this->isVerified = false;
        $this->isFavorite = false;

        $this->createdAt = new \DateTimeImmutable();

        // Vérifie combien de favoris existent déjà
        $existingFavorites = $owner->getSafetyContacts()->filter(fn(SafetyContact $contact) => $contact->isFavorite());

        if ($existingFavorites->count() < 2) {
            // Ajoute ce contact comme favori si on a moins de 2 favoris
            $this->isFavorite = true;

            // Force l'association avec la liste Default
            foreach ($owner->getContactLists() as $list) {
                if ($list->isDefault()) {
                    $this->addContactList($list);
                    break;
                }
            }
        } 

        foreach ($contactLists as $list) {
            $this->addContactList($list);
        }
    
    }

    public function updateSafetyContact(
        string $email,
        string $firstName,
        string $lastName,
        string $phoneNumber,
        string $country,
        ?string $picturePath,
        bool $isFavorite,
        array $contactLists = []
    ): void {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phoneNumber = $phoneNumber;
        $this->country = $country;
        $this->picturePath = $picturePath;
        $this->isFavorite = $isFavorite;
        $this->updatedAt = new \DateTimeImmutable();

        $this->contactLists->clear();
        foreach ($contactLists as $list) {
            $this->addContactList($list);
        }
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

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): self
    {
        $this->isFavorite = $isFavorite;
        return $this;
    }

    public function getPicturePath(): ?string
    {
        return $this->picturePath;
    }

    public function setPicturePath(?string $picturePath): static
    {
        $this->picturePath = $picturePath;

        return $this;
    }

    public function getContactLists(): Collection
    {
        return $this->contactLists;
    }

    public function addContactList(ContactList $list): static
    {
        if (!$this->contactLists->contains($list)) {
            $this->contactLists[] = $list;
            $list->addContact($this); 
        }

        return $this;
    }

    public function removeContactList(ContactList $list): static
    {
        if ($this->contactLists->removeElement($list)) {
            $list->removeContact($this);
        }

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
