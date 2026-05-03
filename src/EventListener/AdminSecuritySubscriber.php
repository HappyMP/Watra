<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User\AdminUser;
use App\Security\AdminRoutePermissionMap;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AdminSecuritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AdminRoutePermissionMap $permissionMap,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 10]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route', '');
        $permission = $this->permissionMap->getRequiredPermission($route);

        if ($permission === null) {
            return;
        }

        if (!$this->authorizationChecker->isGranted('ROLE_ADMINISTRATION_ACCESS')) {
            return;
        }

        // Users with no AdministrationRole have full access (backwards compat)
        if ($this->currentUserHasNoRoles()) {
            return;
        }

        if (!$this->authorizationChecker->isGranted($permission->value)) {
            throw new AccessDeniedException(sprintf(
                'Access denied. Required permission: %s',
                $permission->value,
            ));
        }
    }

    private function currentUserHasNoRoles(): bool
    {
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof AdminUser) {
            return true;
        }

        return $user->getAdministrationRoles()->isEmpty();
    }
}
