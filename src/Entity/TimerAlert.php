<?php

namespace App\Entity;

use App\Repository\TimerAlertRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimerAlertRepository::class)]
class TimerAlert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    
    // L’heure (exacte) à laquelle l’alerte doit être déclenchée
    #[ORM\Column]
    private ?\DateTimeImmutable $alertTime = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne]
    private ?User $updatedByUser = null;

    // Relation obligatoire avec l’Aventure
    #[ORM\OneToOne(inversedBy: 'timerAlert', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Adventure $adventure = null;

    public function __construct()
    {
        $this->isActive = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAlertTime(): ?\DateTimeImmutable
    {
        return $this->alertTime;
    }

    public function setAlertTime(\DateTimeImmutable $alertTime): static
    {
        $this->alertTime = $alertTime;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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

    public function getUpdatedByUser(): ?User
    {
        return $this->updatedByUser;
    }

    public function setUpdatedByUser(?User $updatedByUser): static
    {
        $this->updatedByUser = $updatedByUser;

        return $this;
    }

    public function getAdventure(): ?Adventure
    {
        return $this->adventure;
    }

    public function setAdventure(?Adventure $adventure): static
    {
        $this->adventure = $adventure;

        // Synchronisation côté Adventure (optionnel mais recommandé)
        if ($adventure && $adventure->getTimerAlert() !== $this) {
            $adventure->setTimerAlert($this);
        }
        return $this;
    }

}
