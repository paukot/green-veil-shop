<?php

namespace App\Actions\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarkOrderPaymentFailed
{
    public function __construct(
        protected ReleaseOrderReservations $releaseOrderReservations,
    ) {}

    /**
     * Record a failed payment attempt.
     *
     * A declined card inside an open Stripe Checkout session is not final (the shopper can try
     * another card), so reservations are only released when the failure ends the checkout.
     */
    public function execute(Order $order, ?string $reason = null, bool $releaseReservations = true): void
    {
        DB::transaction(function () use ($order, $reason, $releaseReservations): void {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()->where('id', $order->id)->lockForUpdate()->first();

            if (! $lockedOrder || $lockedOrder->payment_status === PaymentStatus::Paid) {
                return;
            }

            $lockedOrder->update([
                'payment_status' => PaymentStatus::Failed,
                // A terminal failure ends the checkout; a retryable one leaves it open.
                'status' => $releaseReservations ? OrderStatus::Cancelled : $lockedOrder->status,
            ]);

            Payment::updateOrCreate(
                ['order_id' => $lockedOrder->id, 'driver' => 'stripe'],
                [
                    'status' => PaymentStatus::Failed,
                    'failure_reason' => $reason ?? 'Stripe payment failed',
                    'amount' => $lockedOrder->total,
                    'currency' => $lockedOrder->currency,
                ]
            );

            if ($releaseReservations) {
                $this->releaseOrderReservations->execute($lockedOrder);
            }

            Log::warning("Order {$lockedOrder->number} marked payment failed: {$reason}");
        });
    }
}
