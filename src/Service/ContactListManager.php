<?php

namespace App\Service;

use App\Entity\ContactList;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\ContactListRepository;

class ContactListManager
{
    private EntityManagerInterface $em;
    private ContactListRepository $contactListRepository;

    public function __construct(EntityManagerInterface $em, ContactListRepository $contactListRepository)
    {
        $this->em = $em;
        $this->contactListRepository = $contactListRepository;
    }

    /**
     * Crée la liste Default et les 3 listes custom pour un nouvel utilisateur.
     */
    public function initializeDefaultAndCustomLists(User $user): void
    {
        if ($this->hasAlreadyLists($user)) {
            return; // Ne pas recréer les listes si elles existent déjà
        }

        $this->createNamedList($user, 'Default', true);
        $this->createNamedList($user, 'Contact List 1');
        $this->createNamedList($user, 'Contact List 2');
        $this->createNamedList($user, 'Contact List 3');

        $this->em->flush();
    }

    private function createNamedList(User $user, string $name, bool $isDefault = false): void
    {
        $list = new ContactList();
        $list->setName($name);
        $list->setIsDefault($isDefault);
        $list->setOwner($user);

        $this->em->persist($list);
    }

    private function hasAlreadyLists(User $user): bool
    {
        return $this->contactListRepository->count(['owner' => $user]) > 0;
    }
}
