<?php

declare(strict_types=1);

namespace App\EventListener;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class ShopAccountMenuListener
{
    public function addInterestsMenuItem(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $menu
            ->addChild('interests', ['route' => 'app_shop_account_interests', 'routeParameters' => ['_locale' => 'pl_PL']])
            ->setLabel('app.menu.shop.account.interests')
            ->setLabelAttribute('icon', 'tabler:heart')
        ;
    }
}
