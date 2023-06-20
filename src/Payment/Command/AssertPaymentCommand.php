<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Payment\Command;

final class AssertPaymentCommand
{
    public function __construct(private string $payumToken, private int $paymentId)
    {
    }

    public function getPayumToken(): string
    {
        return $this->payumToken;
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }
}
