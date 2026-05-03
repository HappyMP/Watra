<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Security\AdminRoutePermissionMap;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AdminSecuritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AdminRoutePermissionMap $permissionMap,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
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

        // Not yet authenticated — let security firewall handle it
        if (!$this->authorizationChecker->isGranted('ROLE_ADMINISTRATION_ACCESS')) {
            return;
        }

        if (!$this->authorizationChecker->isGranted($permission->value)) {
            throw new AccessDeniedException(sprintf(
                'Access denied. Required permission: %s',
                $permission->value,
            ));
        }
    }
}
