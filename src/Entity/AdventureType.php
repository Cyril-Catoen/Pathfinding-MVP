<?php

namespace App\Entity;

use App\Enum\AdventureTypeList;
use App\Repository\AdventureTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdventureTypeRepository::class)]
class AdventureType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $name = null;

    /**
     * @var Collection<int, Adventure>
     */
    #[ORM\ManyToMany(targetEntity: Adventure::class, mappedBy: 'types')]
    private Collection $adventures;

    public function __construct()
    {
        $this->adventures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection<int, Adventure>
     */
    public function getAdventures(): Collection
    {
        return $this->adventures;
    }

    public function addAdventure(Adventure $adventure): static
    {
        if (!$this->adventures->contains($adventure)) {
            $this->adventures->add($adventure);
            $adventure->addType($this);
        }

        return $this;
    }

    public function removeAdventure(Adventure $adventure): static
    {
        if ($this->adventures->removeElement($adventure)) {
            $adventure->removeType($this);
        }

        return $this;
    }
}
