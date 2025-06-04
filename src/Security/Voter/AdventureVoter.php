<?php

namespace App\Security\Voter;

use App\Entity\Adventure;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;


class AdventureVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const VIEW = 'VIEW';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof Adventure;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var Adventure $adventure */
        $adventure = $subject;

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::EDIT => $this->canEdit($adventure, $user),
            self::VIEW => $this->canView($adventure, $user),
            default => false,
        };
    }

    private function canEdit(Adventure $adventure, User $user): bool
    {
        return $adventure->getOwner() === $user;
    }

    private function canView(Adventure $adventure, User $user): bool
    {
        if ($adventure->getOwner() === $user) {
            return true;
        }

        if ($adventure->getViewAuthorization()->value === 'public') {
            return true;
        }

        if ($adventure->getViewAuthorization()->value === 'selection') {
            return $adventure->getAuthorizedUsers()->contains($user);
        }

        return false; // private
    }
}
