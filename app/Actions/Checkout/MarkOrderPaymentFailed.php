<?php

namespace App\Actions\Checkout;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarkOrderPaymentFailed
{
    public function __construct(
        protected ReleaseOrderStock $releaseOrderStock,
    ) {}

    public function execute(Order $order, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $reason): void {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()->where('id', $order->id)->lockForUpdate()->first();

            if (! $lockedOrder || $lockedOrder->payment_status === PaymentStatus::Paid) {
                return;
            }

            $lockedOrder->update([
                'payment_status' => PaymentStatus::Failed,
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

            // Release reserved inventory back
            $this->releaseOrderStock->execute($lockedOrder);

            Log::warning("Order {$lockedOrder->number} marked payment failed: {$reason}");
        });
    }
}
