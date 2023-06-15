<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Exception;

final class OrderAlreadyCompletedException extends \RuntimeException
{
    public static function occur(string $orderTokenValue): self
    {
        return new self(sprintf('Order with token %d is already completed!', $orderTokenValue));
    }
}
