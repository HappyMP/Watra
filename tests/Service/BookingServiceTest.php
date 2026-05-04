<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Product\ProductVariant;
use App\Exception\InsufficientStockException;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderItemInterface;

final class BookingServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private BookingService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new BookingService($this->em);
    }

    public function testReserveSlotSucceedsWhenStockSufficient(): void
    {
        $variant = new ProductVariant();
        $variant->setCode('VAR-001');
        $variant->setOnHand(10);
        $variant->setOnHold(5);

        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getVariant')->willReturn($variant);
        $item->method('getQuantity')->willReturn(1);

        $order = new Order();
        $order->addItem($item);

        $this->em->expects(self::once())
            ->method('lock')
            ->with($variant, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

        $this->service->reserveSlot($order);
    }

    public function testReserveSlotThrowsWhenStockInsufficient(): void
    {
        $variant = new ProductVariant();
        $variant->setCode('VAR-002');
        $variant->setOnHand(1);
        $variant->setOnHold(1);

        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getVariant')->willReturn($variant);
        $item->method('getQuantity')->willReturn(1);

        $order = new Order();
        $order->addItem($item);

        $this->em->expects(self::once())
            ->method('lock')
            ->with($variant, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('VAR-002');

        $this->service->reserveSlot($order);
    }

    public function testReserveSlotUsesCorrectLockMode(): void
    {
        $variant = new ProductVariant();
        $variant->setCode('VAR-003');
        $variant->setOnHand(5);
        $variant->setOnHold(0);

        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getVariant')->willReturn($variant);
        $item->method('getQuantity')->willReturn(1);

        $order = new Order();
        $order->addItem($item);

        $this->em->expects(self::once())
            ->method('lock')
            ->with(
                self::identicalTo($variant),
                \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE,
            );

        $this->service->reserveSlot($order);
    }
}
