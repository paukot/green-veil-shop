<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Offline\OfflineGateway;
use App\Payments\Stripe\StripeGateway;
use InvalidArgumentException;

class PaymentManager
{
    public function __construct(
        protected StripeGateway $stripeGateway,
    ) {}

    public function gateway(string $driver): PaymentGateway
    {
        return match ($driver) {
            'stripe' => $this->stripeGateway,
            'bank_transfer', 'cod', 'offline' => new OfflineGateway($driver),
            default => throw new InvalidArgumentException("Unsupported payment driver: [{$driver}]."),
        };
    }
}
