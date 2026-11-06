<?php

namespace App\Actions\Checkout;

use App\Models\User;

class CheckoutData
{
    /**
     * @param  array<string, string|null>  $shippingAddress
     * @param  array<string, string|null>  $billingAddress
     */
    public function __construct(
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly ?string $customerPhone,
        public readonly int $deliveryOptionId,
        public readonly int $paymentOptionId,
        public readonly array $shippingAddress,
        public readonly array $billingAddress,
        public readonly ?string $notes = null,
        public readonly ?User $user = null,
        public readonly ?int $promotionId = null,
    ) {}
}
