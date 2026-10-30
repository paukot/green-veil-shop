<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\DTOs\RefundResult;
use Illuminate\Database\Eloquent\Model;

interface PaymentGateway
{
    /**
     * Create a checkout session or process payment for the given order.
     */
    public function createCheckout(Order $order): CheckoutResult;

    /**
     * Process a refund for a payment.
     */
    public function refund(Model $payment, ?int $amountInCents = null, ?string $reason = null): RefundResult;
}
