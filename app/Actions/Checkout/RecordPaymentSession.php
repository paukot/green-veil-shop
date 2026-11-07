<?php

namespace App\Actions\Checkout;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;

class RecordPaymentSession
{
    /**
     * Record payment session id and create or update the unpaid payment record.
     */
    public function execute(Order $order, string $driver, ?string $sessionId): void
    {
        if (empty($sessionId)) {
            return;
        }

        $order->update(['stripe_session_id' => $sessionId]);

        Payment::updateOrCreate(
            ['order_id' => $order->id, 'driver' => $driver],
            [
                'stripe_session_id' => $sessionId,
                'status' => PaymentStatus::Unpaid,
                'amount' => $order->total,
                'currency' => $order->currency,
            ]
        );
    }
}
