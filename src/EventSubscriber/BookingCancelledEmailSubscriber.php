<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

final class BookingCancelledEmailSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SenderInterface $emailSender,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.sylius_order.completed.cancel' => 'onOrderCancelled',
        ];
    }

    public function onOrderCancelled(CompletedEvent $event): void
    {
        /** @var OrderInterface $order */
        $order = $event->getSubject();

        $customer = $order->getCustomer();
        if ($customer === null) {
            return;
        }

        $email = $customer->getEmail();
        if (empty($email)) {
            return;
        }

        $this->emailSender->send(
            'booking_cancelled',
            [$email],
            [
                'order'      => $order,
                'channel'    => $order->getChannel(),
                'localeCode' => $order->getLocaleCode() ?? 'pl_PL',
            ],
        );
    }
}
