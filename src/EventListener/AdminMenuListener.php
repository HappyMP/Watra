<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User\AdminUser;
use App\Security\Permission;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AdminMenuListener
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function removeHiddenMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        if ($configuration = $menu->getChild('configuration')) {
            $configuration->removeChild('shipping_methods');
            $configuration->removeChild('shipping_categories');
            $configuration->removeChild('tax_categories');
            $configuration->removeChild('tax_rates');
            $configuration->removeChild('zones');
            $configuration->removeChild('exchange_rates');
            $configuration->removeChild('payment_methods');
        }

        if ($marketing = $menu->getChild('marketing')) {
            $marketing->removeChild('promotions');
            $marketing->removeChild('catalog_promotions');
        }

        if ($sales = $menu->getChild('sales')) {
            $sales->removeChild('mollie_subscriptions');
        }

        $menu->removeChild('official_support');
        $menu->removeChild('sylius.ui.administration');
    }

    public function addWatraMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $watra = $menu->addChild('watra')
            ->setLabel('app.menu.admin.main.watra.header')
            ->setAttribute('data-test-watra-menu', true);

        if ($this->can(Permission::CITY_MANAGE)) {
            $watra->addChild('cities', ['route' => 'app_admin_city_index'])
                ->setLabel('app.menu.admin.main.watra.cities')
                ->setLabelAttribute('icon', 'tabler:map-pin');
        }

        if ($this->can(Permission::VENUE_MANAGE)) {
            $watra->addChild('venues', ['route' => 'app_admin_venue_index'])
                ->setLabel('app.menu.admin.main.watra.venues')
                ->setLabelAttribute('icon', 'tabler:building');
        }

        if ($this->can(Permission::ROLE_MANAGE)) {
            $watra->addChild('administration_roles', ['route' => 'app_admin_administration_role_index'])
                ->setLabel('app.menu.admin.main.watra.administration_roles')
                ->setLabelAttribute('icon', 'tabler:shield-check');
        }
    }

    /**
     * Users with no AdministrationRole assigned have full access (backwards compat
     * with the existing admin@watra.pl account). Only users who have at least one
     * role assigned are subject to per-permission checks.
     */
    private function can(Permission $permission): bool
    {
        if ($this->currentUserHasNoRoles()) {
            return true;
        }

        return $this->authorizationChecker->isGranted($permission->value);
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
