<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\InsufficientStockException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class BookingService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function reserveSlot(OrderInterface $order): void
    {
        foreach ($order->getItems() as $item) {
            /** @var OrderItemInterface $item */
            /** @var ProductVariantInterface $variant */
            $variant = $item->getVariant();

            $this->em->lock($variant, LockMode::PESSIMISTIC_WRITE);

            $available = $variant->getOnHand() - $variant->getOnHold();
            if ($available < $item->getQuantity()) {
                throw new InsufficientStockException(
                    (string) $variant->getCode(),
                    $available,
                    $item->getQuantity(),
                );
            }
        }
    }
}
