<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Product\ProductVariant;
use App\Exception\InsufficientStockException;
use App\Service\BookingService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderItemInterface;

final class BookingRaceConditionTest extends TestCase
{
    /**
     * Simulates two concurrent bookings of the last slot.
     *
     * First call acquires the pessimistic lock and succeeds.
     * Second call (simulated by manually exhausting onHand) must throw InsufficientStockException.
     *
     * In production, LockMode::PESSIMISTIC_WRITE (SELECT FOR UPDATE) on PostgreSQL ensures
     * the second transaction blocks until the first commits, then sees onHand=0 and fails.
     */
    public function testSecondBookingFailsWhenLastSlotTaken(): void
    {
        $variant = new ProductVariant();
        $variant->setCode('RACE-VAR-001');
        $variant->setOnHand(1);
        $variant->setOnHold(0);

        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getVariant')->willReturn($variant);
        $item->method('getQuantity')->willReturn(1);

        $order = new Order();
        $order->addItem($item);

        // First booking succeeds
        $emForFirst = $this->createMock(EntityManagerInterface::class);
        $emForFirst->expects(self::once())
            ->method('lock')
            ->with($variant, LockMode::PESSIMISTIC_WRITE);

        (new BookingService($emForFirst))->reserveSlot($order);

        // Simulate first booking committed: onHand exhausted
        $variant->setOnHand(0);

        // Second booking must fail
        $emForSecond = $this->createMock(EntityManagerInterface::class);
        $emForSecond->expects(self::once())
            ->method('lock')
            ->with($variant, LockMode::PESSIMISTIC_WRITE);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessageMatches('/RACE-VAR-001/');

        (new BookingService($emForSecond))->reserveSlot($order);
    }

    public function testPessimisticWriteLockIsAlwaysUsed(): void
    {
        $variant = new ProductVariant();
        $variant->setCode('LOCK-CHECK-001');
        $variant->setOnHand(99);
        $variant->setOnHold(0);

        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getVariant')->willReturn($variant);
        $item->method('getQuantity')->willReturn(1);

        $order = new Order();
        $order->addItem($item);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('lock')
            ->with(
                self::identicalTo($variant),
                LockMode::PESSIMISTIC_WRITE,
            );

        (new BookingService($em))->reserveSlot($order);
    }
}
