<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Provider;

use CommerceWeavers\SyliusSaferpayPlugin\Exception\OrderAlreadyCompletedException;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class OrderProvider implements OrderProviderInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    public function provideForAssert(string $tokenValue): OrderInterface
    {
        return $this->provideByTokenValue($tokenValue);
    }

    public function provideForCapture(string $tokenValue): OrderInterface
    {
        return $this->provideByTokenValue($tokenValue);
    }

    private function provideByTokenValue(string $tokenValue): OrderInterface
    {
        /** @var OrderInterface|null $order */
        $order = $this->orderRepository->findOneByTokenValue($tokenValue);
        if (null !== $order) {
            return $order;
        }

        /** @var OrderInterface|null $order */
        $order = $this->orderRepository->findOneBy(['tokenValue' => $tokenValue]);
        if ($order !== null && $order->getCheckoutState() === OrderCheckoutStates::STATE_COMPLETED) {
            throw OrderAlreadyCompletedException::occur($tokenValue);
        }

        throw new NotFoundHttpException(sprintf('Order with token "%s" does not exist.', $tokenValue));
    }
}
