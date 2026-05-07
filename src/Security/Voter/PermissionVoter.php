<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User\AdminUser;
use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @extends Voter<string, mixed>
 */
final class PermissionVoter extends Voter
{
    /** @param array<mixed> $attributes */
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        $relevant = array_filter(
            $attributes,
            static fn (mixed $a): bool => is_string($a) && in_array($a, Permission::all(), true),
        );

        if (empty($relevant)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $user = $token->getUser();

        if (!$user instanceof AdminUser) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // No custom roles assigned → abstain, let security.yaml ROLE_ADMINISTRATION_ACCESS decide
        if ($user->getAdministrationRoles()->isEmpty()) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        foreach ($relevant as $attribute) {
            if ($this->voteOnAttribute($attribute, $subject, $token)) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return VoterInterface::ACCESS_DENIED;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, Permission::all(), true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof AdminUser) {
            return false;
        }

        foreach ($user->getAdministrationRoles() as $role) {
            if ($role->hasPermission($attribute)) {
                return true;
            }
        }

        return false;
    }
}
