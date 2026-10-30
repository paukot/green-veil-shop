<?php

namespace App\Payments\DTOs;

class CheckoutResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $sessionId = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function success(string $redirectUrl, ?string $sessionId = null): self
    {
        return new self(
            successful: true,
            redirectUrl: $redirectUrl,
            sessionId: $sessionId,
        );
    }

    public static function completedDirectly(): self
    {
        return new self(
            successful: true,
            redirectUrl: null,
            sessionId: null,
        );
    }

    public static function failed(string $errorMessage): self
    {
        return new self(
            successful: false,
            redirectUrl: null,
            sessionId: null,
            errorMessage: $errorMessage,
        );
    }
}
