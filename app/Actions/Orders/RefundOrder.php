<?php

namespace App\Actions\Orders;

use App\Actions\Checkout\ReleaseOrderReservations;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\DTOs\RefundResult;
use App\Payments\PaymentManager;
use Illuminate\Support\Facades\DB;

class RefundOrder
{
    public function __construct(
        protected PaymentManager $paymentManager,
        protected ReleaseOrderReservations $releaseOrderReservations,
    ) {}

    /**
     * Refund part or all of an order's remaining balance through its payment gateway.
     *
     * The order row stays locked for the gateway call, so a double-submitted refund waits and
     * then sees the reduced balance instead of refunding twice.
     */
    public function execute(Order $order, float $amount, string $reason = 'requested_by_customer', bool $restock = false): RefundResult
    {
        return DB::transaction(function () use ($order, $amount, $reason, $restock): RefundResult {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()->with('paymentOption')->whereKey($order->id)->lockForUpdate()->first();

            if (! $lockedOrder) {
                return RefundResult::failed('The order no longer exists.');
            }

            $amount = round($amount, 2);
            $refundable = $lockedOrder->refundableAmount();

            if ($amount <= 0 || $amount > $refundable) {
                return RefundResult::failed('The refund amount must be between €0.01 and the remaining €'.number_format($refundable, 2).'.');
            }

            $driver = $lockedOrder->paymentOption->driver ?? ($lockedOrder->stripe_payment_intent_id ? 'stripe' : 'offline');
            $result = $this->paymentManager->gateway($driver)->refund($lockedOrder, (int) round($amount * 100), $reason);

            if (! $result->successful) {
                return $result;
            }

            $refundedAmount = round((float) $lockedOrder->refunded_amount + $amount, 2);
            $isFullRefund = $refundedAmount >= (float) $lockedOrder->total;
            $paymentStatus = $isFullRefund ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded;

            $lockedOrder->update([
                'refunded_amount' => $refundedAmount,
                'payment_status' => $paymentStatus,
                'status' => $isFullRefund ? OrderStatus::Refunded : $lockedOrder->status,
            ]);

            Payment::query()->where('order_id', $lockedOrder->id)->update(['status' => $paymentStatus]);

            if ($restock) {
                $this->releaseOrderReservations->execute($lockedOrder);
            }

            return $result;
        });
    }
}
