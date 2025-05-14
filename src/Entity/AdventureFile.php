<?php

namespace App\Entity;

use App\Enum\AdventureFileType;
use App\Enum\ViewAuthorization;
use App\Repository\AdventureFileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdventureFileRepository::class)]
class AdventureFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalUrl = null;

    #[ORM\Column]
    private ?bool $isExternal = false;

    #[ORM\Column(enumType: AdventureFileType::class)]
    private AdventureFileType $type;

    #[ORM\Column(enumType: ViewAuthorization::class)]
    private ViewAuthorization $viewAuthorization;

    #[ORM\Column]
    private ?\DateTimeImmutable $uploadedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?int $size = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $fileExtension = null;

    #[ORM\ManyToOne(inversedBy: 'adventureFiles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Adventure $adventure = null;

    public function __construct() {
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function isVisibleTo(?User $user): bool {
        if ($this->getViewAuthorization() === ViewAuthorization::PUBLIC) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($this->getAdventure()->getOwner() === $user) {
            return true;
        }

        if ($this->getViewAuthorization() === ViewAuthorization::SELECTION) {
            return $this->getAdventure()->getAuthorizedUsers()->contains($user);
        }

        return false;
    }

    public function isEditableBy(?User $user): bool
    {
        return $user && $this->getAdventure()?->getOwner() === $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }

    public function setExternalUrl(?string $externalUrl): static
    {
        $this->externalUrl = $externalUrl;

        return $this;
    }

    public function isExternal(): ?bool
    {
        return $this->isExternal;
    }

    public function setIsExternal(bool $isExternal): static
    {
        $this->isExternal = $isExternal;

        return $this;
    }

    public function getType(): AdventureFileType
    {
        return $this->type;
    }

    public function setType(AdventureFileType $type): static
    {
        $this->type = $type;

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

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;

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

    public function getAdventure(): ?Adventure
    {
        return $this->adventure;
    }

    public function setAdventure(?Adventure $adventure): static
    {
        $this->adventure = $adventure;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getFileExtension(): ?string
    {
        return $this->fileExtension;
    }

    public function setFileExtension(?string $fileExtension): static
    {
        $this->fileExtension = $fileExtension;
        return $this;
    }
}
