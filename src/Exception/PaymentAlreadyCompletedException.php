<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Exception;

final class PaymentAlreadyCompletedException extends \RuntimeException
{
    public static function occur(int $paymentId, string $orderTokenValue): self
    {
        return new self(sprintf(
            'Payment with id %d from order with token %s is already completed!',
            $paymentId,
            $orderTokenValue,
        ));
    }
}
