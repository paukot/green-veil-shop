<?php

namespace App\Jobs;

use App\Actions\Checkout\MarkOrderPaid;
use App\Actions\Checkout\MarkOrderPaymentFailed;
use App\Actions\Checkout\ReleaseOrderStock;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StripeEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandleStripeWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $stripeEventRecordId,
    ) {}

    public function handle(
        MarkOrderPaid $markOrderPaid,
        MarkOrderPaymentFailed $markOrderPaymentFailed,
        ReleaseOrderStock $releaseOrderStock,
    ): void {
        $eventRecord = StripeEvent::find($this->stripeEventRecordId);

        if (! $eventRecord || $eventRecord->processed_at !== null) {
            return;
        }

        $type = $eventRecord->type;
        $payload = $eventRecord->payload;
        $dataObject = $payload['data']['object'] ?? [];

        $resolvedOrder = $this->resolveOrder($dataObject);
        if ($resolvedOrder && $eventRecord->order_id !== $resolvedOrder->id) {
            $eventRecord->update(['order_id' => $resolvedOrder->id]);
        }

        match ($type) {
            'checkout.session.completed' => $this->handleSessionCompleted($dataObject, $markOrderPaid),
            'checkout.session.async_payment_succeeded' => $this->handleAsyncPaymentSucceeded($dataObject, $markOrderPaid),
            'checkout.session.async_payment_failed' => $this->handleAsyncPaymentFailed($dataObject, $markOrderPaymentFailed),
            'checkout.session.expired' => $this->handleSessionExpired($dataObject, $releaseOrderStock),
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($dataObject, $markOrderPaid),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($dataObject, $markOrderPaymentFailed),
            'charge.succeeded' => $this->handleChargeSucceeded($dataObject, $markOrderPaid),
            'charge.refunded' => $this->handleChargeRefunded($dataObject, $releaseOrderStock),
            default => Log::info("Unhandled Stripe webhook event: {$type}"),
        };

        $eventRecord->update(['processed_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $sessionData
     *
     * @throws \Throwable
     */
    protected function handleSessionCompleted(array $sessionData, MarkOrderPaid $markOrderPaid): void
    {
        $order = $this->resolveOrder($sessionData);

        if (! $order) {
            Log::warning('Stripe webhook checkout.session.completed received but matching order not found', $sessionData);

            return;
        }

        $paymentStatus = $sessionData['payment_status'] ?? 'unpaid';

        if ($paymentStatus === PaymentStatus::Paid->value) {
            $markOrderPaid->execute($order, $sessionData);
        } else {
            Log::info("Order {$order->number} checkout session completed with payment_status: {$paymentStatus} (awaiting async confirmation).");
        }
    }

    /**
     * @param  array<string, mixed>  $sessionData
     */
    protected function handleAsyncPaymentSucceeded(array $sessionData, MarkOrderPaid $markOrderPaid): void
    {
        $order = $this->resolveOrder($sessionData);

        if (! $order) {
            return;
        }

        $markOrderPaid->execute($order, $sessionData);
    }

    /**
     * @param  array<string, mixed>  $sessionData
     */
    protected function handleAsyncPaymentFailed(array $sessionData, MarkOrderPaymentFailed $markOrderPaymentFailed): void
    {
        $order = $this->resolveOrder($sessionData);

        if (! $order) {
            return;
        }

        $reason = $sessionData['last_payment_error']['message'] ?? 'Asynchronous payment failed';
        $markOrderPaymentFailed->execute($order, $reason);
    }

    /**
     * @param  array<string, mixed>  $sessionData
     */
    protected function handleSessionExpired(array $sessionData, ReleaseOrderStock $releaseOrderStock): void
    {
        $order = $this->resolveOrder($sessionData);

        if (! $order) {
            return;
        }

        DB::transaction(function () use ($order, $releaseOrderStock): void {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()->where('id', $order->id)->lockForUpdate()->first();

            if (! $lockedOrder || $lockedOrder->payment_status === PaymentStatus::Paid) {
                return;
            }

            $lockedOrder->update([
                'status' => OrderStatus::Cancelled,
                'payment_status' => PaymentStatus::Expired,
            ]);

            Payment::updateOrCreate(
                ['order_id' => $lockedOrder->id, 'driver' => 'stripe'],
                [
                    'status' => PaymentStatus::Expired,
                    'failure_reason' => 'Stripe checkout session expired without completion',
                    'amount' => $lockedOrder->total,
                    'currency' => $lockedOrder->currency,
                ]
            );

            $releaseOrderStock->execute($lockedOrder);

            Log::info("Order {$lockedOrder->number} cancelled due to expired Stripe session.");
        });
    }

    /**
     * @param  array<string, mixed>  $paymentIntentData
     */
    protected function handlePaymentIntentSucceeded(array $paymentIntentData, MarkOrderPaid $markOrderPaid): void
    {
        $order = $this->resolveOrder($paymentIntentData);

        if (! $order) {
            Log::warning('Stripe webhook payment_intent.succeeded received but matching order not found', $paymentIntentData);

            return;
        }

        $markOrderPaid->execute($order, $paymentIntentData);
    }

    /**
     * @param  array<string, mixed>  $paymentIntentData
     */
    protected function handlePaymentIntentFailed(array $paymentIntentData, MarkOrderPaymentFailed $markOrderPaymentFailed): void
    {
        $order = $this->resolveOrder($paymentIntentData);

        if (! $order) {
            return;
        }

        $reason = $paymentIntentData['last_payment_error']['message'] ?? 'Payment intent failed';
        $markOrderPaymentFailed->execute($order, $reason);
    }

    /**
     * @param  array<string, mixed>  $chargeData
     */
    protected function handleChargeSucceeded(array $chargeData, MarkOrderPaid $markOrderPaid): void
    {
        $order = $this->resolveOrder($chargeData);

        if (! $order) {
            return;
        }

        $markOrderPaid->execute($order, $chargeData);
    }

    /**
     * @param  array<string, mixed>  $chargeData
     */
    protected function handleChargeRefunded(array $chargeData, ReleaseOrderStock $releaseOrderStock): void
    {
        $order = $this->resolveOrder($chargeData);

        if (! $order) {
            $paymentIntentId = $chargeData['payment_intent'] ?? null;
            $chargeId = $chargeData['id'] ?? null;

            if ($paymentIntentId) {
                $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();
            }
            if (! $order && $chargeId) {
                $order = Order::where('stripe_charge_id', $chargeId)->first();
            }
            if (! $order && $paymentIntentId) {
                $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();
                $order = $payment?->order;
            }
        }

        if (! $order) {
            Log::warning('Stripe webhook charge.refunded received but matching order not found', $chargeData);

            return;
        }

        $amountRefunded = (int) ($chargeData['amount_refunded'] ?? 0);
        $amountTotal = (int) ($chargeData['amount'] ?? round($order->total * 100));
        $isFullRefund = ($amountRefunded >= $amountTotal);

        $refundStatus = $isFullRefund ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded;

        $order->update([
            'payment_status' => $refundStatus,
            'status' => $isFullRefund ? OrderStatus::Refunded : $order->status,
        ]);

        Payment::where('order_id', $order->id)->update([
            'status' => $refundStatus,
        ]);

        if ($isFullRefund && ! in_array($order->status, [OrderStatus::Shipped, OrderStatus::Delivered])) {
            $releaseOrderStock->execute($order);
        }

        Log::info("Order {$order->number} refund recorded ({$refundStatus->value}).");
    }

    /**
     * @param  array<string, mixed>  $dataObject
     */
    protected function resolveOrder(array $dataObject): ?Order
    {
        // 1. Direct metadata: order_id or client_reference_id
        $orderId = $dataObject['metadata']['order_id'] ?? $dataObject['client_reference_id'] ?? null;
        if (! empty($orderId)) {
            $order = Order::find($orderId);
            if ($order instanceof Order) {
                return $order;
            }
        }

        // 2. Direct metadata: public_id
        $publicId = $dataObject['metadata']['public_id'] ?? null;
        if (! empty($publicId)) {
            $order = Order::where('public_id', $publicId)->first();
            if ($order) {
                return $order;
            }
        }

        $rawId = (string) ($dataObject['id'] ?? '');
        $objectType = (string) ($dataObject['object'] ?? '');

        // 3. Checkout Session ID
        $sessionId = str_starts_with($rawId, 'cs_') || $objectType === 'checkout.session' ? $rawId : null;
        if (! empty($sessionId)) {
            $order = Order::where('stripe_session_id', $sessionId)->first();
            if ($order) {
                return $order;
            }

            $payment = Payment::where('stripe_session_id', $sessionId)->first();
            if ($payment?->order) {
                return $payment->order;
            }
        }

        // 4. Payment Intent ID
        $paymentIntentId = $dataObject['payment_intent']
            ?? (str_starts_with($rawId, 'pi_') || $objectType === 'payment_intent' ? $rawId : null);

        if (! empty($paymentIntentId)) {
            $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();
            if ($order) {
                return $order;
            }

            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();
            if ($payment?->order) {
                return $payment->order;
            }
        }

        // 5. Charge ID
        $chargeId = str_starts_with($rawId, 'ch_') || $objectType === 'charge'
            ? $rawId
            : ($dataObject['charge'] ?? null);

        if (! empty($chargeId)) {
            $order = Order::where('stripe_charge_id', $chargeId)->first();
            if ($order) {
                return $order;
            }

            $payment = Payment::where('stripe_charge_id', $chargeId)->first();
            if ($payment?->order) {
                return $payment->order;
            }
        }

        // 6. Guest Order Fallback by customer email + total amount for recent unfulfilled order
        $customerEmail = $dataObject['customer_details']['email']
            ?? $dataObject['receipt_email']
            ?? $dataObject['billing_details']['email']
            ?? null;

        $amountCents = $dataObject['amount_total'] ?? $dataObject['amount'] ?? null;

        if (! empty($customerEmail) && $amountCents !== null) {
            $expectedTotal = round(((int) $amountCents) / 100, 2);
            $order = Order::query()
                ->where('customer_email', $customerEmail)
                ->where('total', $expectedTotal)
                ->where('payment_status', PaymentStatus::Unpaid)
                ->where('status', OrderStatus::PendingPayment)
                ->where('created_at', '>=', now()->subHours(24))
                ->latest('id')
                ->first();

            if ($order) {
                return $order;
            }
        }

        return null;
    }
}
