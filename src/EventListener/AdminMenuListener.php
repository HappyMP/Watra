<?php

declare(strict_types=1);

namespace App\EventListener;

use Sylius\Bundle\AdminBundle\Menu\MainMenuBuilder;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: MainMenuBuilder::EVENT_NAME, method: 'removeUnusedMenuItems')]
final class AdminMenuListener
{
    public function removeUnusedMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $configuration = $menu->getChild('configuration');

        if (null === $configuration) {
            return;
        }

        $configuration->removeChild('shipping_methods');
        $configuration->removeChild('shipping_categories');
        $configuration->removeChild('tax_categories');
        $configuration->removeChild('tax_rates');
        $configuration->removeChild('zones');
        $configuration->removeChild('exchange_rates');
    }
}
