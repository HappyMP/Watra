<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Security\Permission;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AdminMenuListener
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
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
        $checker = $this->authorizationChecker;

        $watra = $menu->addChild('watra')
            ->setLabel('app.menu.admin.main.watra.header')
            ->setAttribute('data-test-watra-menu', true);

        if ($checker->isGranted(Permission::CITY_MANAGE->value)) {
            $watra->addChild('cities', ['route' => 'app_admin_city_index'])
                ->setLabel('app.menu.admin.main.watra.cities')
                ->setLabelAttribute('icon', 'tabler:map-pin');
        }

        if ($checker->isGranted(Permission::VENUE_MANAGE->value)) {
            $watra->addChild('venues', ['route' => 'app_admin_venue_index'])
                ->setLabel('app.menu.admin.main.watra.venues')
                ->setLabelAttribute('icon', 'tabler:building');
        }

        if ($checker->isGranted(Permission::ROLE_MANAGE->value)) {
            $watra->addChild('administration_roles', ['route' => 'app_admin_administration_role_index'])
                ->setLabel('app.menu.admin.main.watra.administration_roles')
                ->setLabelAttribute('icon', 'tabler:shield-check');
        }
    }
}
