<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\BookingService;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Webmozart\Assert\Assert;

#[AsEventListener(
    event: 'workflow.sylius_order_checkout.completed.complete',
    priority: 250,
)]
final class FreeOrderPaymentListener
{
    public function __construct(
        private readonly StateMachineInterface $stateMachine,
        private readonly BookingService $bookingService,
    ) {
    }

    public function __invoke(CompletedEvent $event): void
    {
        /** @var OrderInterface $order */
        $order = $event->getSubject();
        Assert::isInstanceOf($order, OrderInterface::class);

        $this->bookingService->reserveSlot($order);

        if ($order->getTotal() > 0) {
            return;
        }

        foreach ($order->getPayments() as $payment) {
            if ($payment->getState() !== PaymentInterface::STATE_CART) {
                continue;
            }

            if ($this->stateMachine->can($payment, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_COMPLETE)) {
                $this->stateMachine->apply($payment, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_COMPLETE);
            }
        }
    }
}
