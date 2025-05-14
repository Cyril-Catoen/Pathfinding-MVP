<?php

namespace App\Entity;

use App\Enum\DeliveryMethod;
use App\Enum\SafetyAlertStatus;
use App\Enum\SafetyAlertReason;
use App\Repository\SafetyAlertRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SafetyAlertRepository::class)]
class SafetyAlert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $triggeredAt = null;

    #[ORM\Column(enumType: SafetyAlertStatus::class)]
    private SafetyAlertStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: Types::JSON)]
    private array $deliveryMethod = [];

    #[ORM\Column(enumType: SafetyAlertReason::class)]
    private SafetyAlertReason $reason;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $acknowledgedAt = null;

    #[ORM\ManyToOne(inversedBy: 'safetyAlerts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Adventure $adventure = null;

    #[ORM\ManyToOne]
    private ?User $triggeredBy = null;

    /**
     * @var Collection<int, SafetyContact>
     */
    #[ORM\ManyToMany(targetEntity: SafetyContact::class, inversedBy: 'safetyAlerts')]
    private Collection $notifiedContacts;

    public function __construct()
    {
        $this->notifiedContacts = new ArrayCollection();
        $this->triggeredAt = new \DateTimeImmutable(); // déclenché à la création par défaut
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTriggeredAt(): ?\DateTimeImmutable
    {
        return $this->triggeredAt;
    }

    public function setTriggeredAt(\DateTimeImmutable $triggeredAt): static
    {
        $this->triggeredAt = $triggeredAt;

        return $this;
    }

    public function getStatus(): SafetyAlertStatus
    {
        return $this->status;
    }

    public function setStatus(SafetyAlertStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getDeliveryMethod(): array
    {
        return $this->deliveryMethod;
    }

    public function setDeliveryMethod(array $deliveryMethod): static
    {
        $this->deliveryMethod = $deliveryMethod;

        return $this;
    }

    public function getAcknowledgedAt(): ?\DateTimeImmutable
    {
        return $this->acknowledgedAt;
    }
    public function setAcknowledgedAt(?\DateTimeImmutable $acknowledgedAt): static
    {
        $this->acknowledgedAt = $acknowledgedAt;

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

    /**
     * @return Collection<int, SafetyContact>
     */
    public function getNotifiedContacts(): Collection
    {
        return $this->notifiedContacts;
    }

    public function addNotifiedContact(SafetyContact $notifiedContact): static
    {
        if (!$this->notifiedContacts->contains($notifiedContact)) {
            $this->notifiedContacts->add($notifiedContact);
        }

        return $this;
    }

    public function removeNotifiedContact(SafetyContact $notifiedContact): static
    {
        $this->notifiedContacts->removeElement($notifiedContact);

        return $this;
    }
}
