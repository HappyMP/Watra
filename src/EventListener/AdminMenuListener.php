<?php

declare(strict_types=1);

namespace App\EventListener;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
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

        $menu->removeChild('official_support');
    }
}
