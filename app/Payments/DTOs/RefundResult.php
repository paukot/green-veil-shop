<?php

namespace App\Payments\DTOs;

class RefundResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $refundId = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function success(?string $refundId = null): self
    {
        return new self(
            successful: true,
            refundId: $refundId,
        );
    }

    public static function failed(string $errorMessage): self
    {
        return new self(
            successful: false,
            refundId: null,
            errorMessage: $errorMessage,
        );
    }
}
