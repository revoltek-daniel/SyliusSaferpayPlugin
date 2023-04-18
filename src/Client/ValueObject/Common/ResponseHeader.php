<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Client\ValueObject\Common;

class ResponseHeader
{
    public function __construct(
        private string $specVersion,
        private string $requestId,
    ) {
    }

    public function getSpecVersion(): string
    {
        return $this->specVersion;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['SpecVersion'],
            $data['RequestId'],
        );
    }
}
