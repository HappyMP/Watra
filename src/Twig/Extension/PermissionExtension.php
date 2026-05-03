<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PermissionExtension extends AbstractExtension
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_permission', $this->hasPermission(...)),
        ];
    }

    public function hasPermission(string $permission): bool
    {
        return $this->authorizationChecker->isGranted($permission);
    }
}
