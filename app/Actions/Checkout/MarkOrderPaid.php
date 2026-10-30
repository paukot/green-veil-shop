<?php

namespace App\Actions\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MarkOrderPaid
{
    /**
     * Mark an order and its payment as paid idempotently after verifying amount and currency.
     *
     * @param  array<string, mixed>  $sessionData
     */
    public function execute(Order $order, array $sessionData): bool
    {
        return DB::transaction(function () use ($order, $sessionData): bool {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()->where('id', $order->id)->lockForUpdate()->first();

            if (! $lockedOrder) {
                return false;
            }

            // If already marked as paid, return true idempotently
            if ($lockedOrder->payment_status === PaymentStatus::Paid) {
                return true;
            }

            $amountTotal = $sessionData['amount_total'] ?? null;
            $currency = strtolower($sessionData['currency'] ?? '');
            $expectedCurrency = strtolower($lockedOrder->currency ?: 'eur');
            $expectedAmount = (int) round($lockedOrder->total * 100);

            // Assert currency matches
            if ($currency !== '' && $currency !== $expectedCurrency) {
                Log::alert("Stripe payment currency mismatch for order {$lockedOrder->id}", [
                    'expected' => $expectedCurrency,
                    'received' => $currency,
                    'order_id' => $lockedOrder->id,
                ]);

                return false;
            }

            // Assert amount matches
            if ($amountTotal !== null && (int) $amountTotal !== $expectedAmount) {
                Log::alert("Stripe payment amount mismatch for order {$lockedOrder->id}", [
                    'expected_cents' => $expectedAmount,
                    'received_cents' => $amountTotal,
                    'order_id' => $lockedOrder->id,
                ]);

                return false;
            }

            $paymentIntentId = $sessionData['payment_intent'] ?? null;
            $sessionId = $sessionData['id'] ?? $lockedOrder->stripe_session_id;

            // Mark order as paid and processing
            $lockedOrder->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => OrderStatus::Processing,
                'paid_at' => now(),
                'stripe_payment_intent_id' => $paymentIntentId,
                'stripe_session_id' => $sessionId,
            ]);

            // Update or create Payment record
            Payment::updateOrCreate(
                ['order_id' => $lockedOrder->id, 'driver' => 'stripe'],
                [
                    'stripe_session_id' => $sessionId,
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'status' => PaymentStatus::Paid,
                    'amount' => $lockedOrder->total,
                    'currency' => $lockedOrder->currency,
                    'paid_at' => now(),
                ]
            );

            Log::info("Order {$lockedOrder->number} ({$lockedOrder->id}) marked as paid via Stripe.");

            Mail::to($lockedOrder->customer_email)->queue(
                new OrderConfirmationMail($lockedOrder)
            );

            return true;
        });
    }
}
