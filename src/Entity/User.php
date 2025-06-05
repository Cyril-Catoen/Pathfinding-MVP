<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(min: 5)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
        message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.'
    )]
    private ?string $password = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Assert\Regex(
        pattern: '/^[\p{L} -]+$/u', 
        message: "Le nom ne peut contenir que des lettres, espaces ou tirets.")]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Assert\Regex(
        pattern: '/^[\p{L} -]+$/u', 
        message: "Le nom ne peut contenir que des lettres, espaces ou tirets.")]
    private ?string $surname = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\LessThanOrEqual(
        value: 'today -18 years',
        message: 'Vous devez avoir au moins 18 ans pour vous inscrire.')]
    private ?\DateTime $birthdate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $path = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pathNumber = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postcode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    // #[Assert\NotBlank]
    // #[Assert\Length(min: 2)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $picturePath = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $registrationAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLogin = null;

    #[ORM\Column(nullable: true)]
    private ?array $lastKnownPosition = null;

    /**
     * @var Collection<int, Adventure>
     */
    #[ORM\ManyToMany(targetEntity: Adventure::class, mappedBy: "authorizedUsers")]
    private Collection $authorizedAdventures;

    /**
     * @var Collection<int, SafetyContact>
     */
    #[ORM\OneToMany(targetEntity: SafetyContact::class, mappedBy: 'user')]
    private Collection $safetyContacts;

    /**
     * @var Collection<int, ContactList>
     */
    #[ORM\OneToMany(targetEntity: ContactList::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $contactLists;

    public function __construct()
    {
        $this->authorizedAdventures = new ArrayCollection();
        $this->safetyContacts = new ArrayCollection();
        $this->registrationAt = new \DateTimeImmutable();
        $this->contactLists = new ArrayCollection(); 
    }

    public function createAdmin(
        string $email,
        string $passwordHashed,
        string $name,
        string $surname,
        \DateTime $birthdate
    ): void {
        $this->email = $email;
        $this->password = $passwordHashed;
        $this->roles = ['ROLE_ADMIN'];
        $this->name = $name;
        $this->surname = $surname;
        $this->birthdate = $birthdate;
    }

    public function createUser(
        string $email,
        string $passwordHashed,
        string $name,
        string $surname,
        \DateTime $birthdate
    ): void {
        $this->email = $email;
        $this->password = $passwordHashed;
        $this->roles = ['ROLE_USER'];
        $this->name = $name;
        $this->surname = $surname;
        $this->birthdate = $birthdate;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->resetTokenExpiresAt = $expiresAt;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getBirthdate(): ?\DateTime
    {
        return $this->birthdate;
    }

    public function setBirthdate(\DateTime $birthdate): static
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getPathNumber(): ?string
    {
        return $this->pathNumber;
    }

    public function setPathNumber(?string $pathNumber): static
    {
        $this->pathNumber = $pathNumber;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(?string $postcode): static
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

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

    public function getRegistrationAt(): ?\DateTimeImmutable
    {
        return $this->registrationAt;
    }

    public function setRegistrationAt(\DateTimeImmutable $registrationAt): static
    {
        $this->registrationAt = $registrationAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeImmutable $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getLastKnownPosition(): ?array
    {
        return $this->lastKnownPosition;
    }

    public function setLastKnownPosition(?array $lastKnownPosition): static
    {
        $this->lastKnownPosition = $lastKnownPosition;

        return $this;
    }

    /**
     * @return Collection<int, Adventure>
     */
    public function getAuthorizedAdventures(): Collection
    {
        return $this->authorizedAdventures;
    }

    public function addAuthorizedAdventure(Adventure $adventure): static
    {
        if (!$this->authorizedAdventures->contains($adventure)) {
            $this->authorizedAdventures->add($adventure);
            $adventure->addAuthorizedUser($this);
        }

        return $this;
    }

    public function removeAuthorizedAdventure(Adventure $adventure): static
    {
        if ($this->authorizedAdventures->removeElement($adventure)) {
            $adventure->removeAuthorizedUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, SafetyContact>
     */
    public function getSafetyContacts(): Collection
    {
        return $this->safetyContacts;
    }

    public function addSafetyContact(SafetyContact $safetyContact): static
    {
        if (!$this->safetyContacts->contains($safetyContact)) {
            $this->safetyContacts->add($safetyContact);
            $safetyContact->setUser($this);
        }

        return $this;
    }

    public function removeSafetyContact(SafetyContact $safetyContact): static
    {
        if ($this->safetyContacts->removeElement($safetyContact)) {
            // set the owning side to null (unless already changed)
            if ($safetyContact->getUser() === $this) {
                $safetyContact->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContactList>
     */
    public function getContactLists(): Collection
    {
        return $this->contactLists;
    }

    public function addContactList(ContactList $contactList): static
    {
        if (!$this->contactLists->contains($contactList)) {
            $this->contactLists->add($contactList);
            $contactList->setOwner($this);
        }

        return $this;
    }

    public function removeContactList(ContactList $contactList): static
    {
        if ($this->contactLists->removeElement($contactList)) {
            // set the owning side to null (unless already changed)
            if ($contactList->getOwner() === $this) {
                $contactList->setOwner(null);
            }
        }

        return $this;
    }

    // PicturePath (photo de profil)
    public function getPicturePath(): ?string
    {
        return $this->picturePath;
    }

    public function setPicturePath(?string $picturePath): static
    {
        $this->picturePath = $picturePath;
        return $this;
    }

    // Description (bio, présentation)
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

}
