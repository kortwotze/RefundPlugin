<?php

declare(strict_types=1);

namespace spec\Sylius\RefundPlugin\Refunder;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\RefundPlugin\Creator\RefundCreatorInterface;
use Sylius\RefundPlugin\Event\UnitRefunded;
use Sylius\RefundPlugin\Model\OrderItemUnitRefund;
use Sylius\RefundPlugin\Model\RefundType;
use Sylius\RefundPlugin\Refunder\RefunderInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class OrderItemUnitsRefunderSpec extends ObjectBehavior
{
    function let(
        RefundCreatorInterface $refundCreator,
        MessageBusInterface $eventBus
    ): void {
        $this->beConstructedWith($refundCreator, $eventBus);
    }

    function it_implements_refunder_interface(): void
    {
        $this->shouldImplement(RefunderInterface::class);
    }

    function it_creates_refund_for_each_unit_and_dispatch_proper_event(
        RefundCreatorInterface $refundCreator,
        MessageBusInterface $eventBus
    ): void {
        $firstUnitRefund = new OrderItemUnitRefund(1, 1500);
        $secondUnitRefund = new OrderItemUnitRefund(3, 1000);

        $refundCreator->__invoke('000222', 1, 1500, RefundType::orderItemUnit())->shouldBeCalled();

        $eventBus->dispatch(Argument::that(function (UnitRefunded $event): bool {
            return
                $event->orderNumber() === '000222' &&
                $event->unitId() === 1 &&
                $event->amount() === 1500
            ;
        }))->shouldBeCalled();

        $refundCreator->__invoke('000222', 3, 1000, RefundType::orderItemUnit())->shouldBeCalled();

        $eventBus->dispatch(Argument::that(function (UnitRefunded $event): bool {
            return
                $event->orderNumber() === '000222' &&
                $event->unitId() === 3 &&
                $event->amount() === 1000
            ;
        }))->shouldBeCalled();

        $this->refundFromOrder([$firstUnitRefund, $secondUnitRefund], '000222')->shouldReturn(2500);
    }
}
