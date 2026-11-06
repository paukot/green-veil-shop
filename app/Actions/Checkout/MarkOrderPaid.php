<?php

namespace App\Actions\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MarkOrderPaid
{
    /**
     * Mark an order and its payment as paid idempotently after verifying amount and currency.
     *
     * @param  array<string, mixed>  $sessionData
     *
     * @throws \Throwable
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

            $amountTotal = $sessionData['amount_total'] ?? $sessionData['amount'] ?? null;
            $currency = strtolower($sessionData['currency'] ?? '');
            $expectedCurrency = strtolower($lockedOrder->currency ?: config('services.stripe.currency'));
            $expectedAmount = (int) round($lockedOrder->total * 100);

            // Assert currency matches
            if (! empty($currency) && $currency !== $expectedCurrency) {
                Log::alert("Stripe payment currency mismatch for order {$lockedOrder->id}", [
                    'expected' => $expectedCurrency,
                    'received' => $currency,
                    'order_id' => $lockedOrder->id,
                ]);

                return false;
            }

            // Assert amount matches
            if (! empty($amountTotal) && (int) $amountTotal !== $expectedAmount) {
                Log::alert("Stripe payment amount mismatch for order {$lockedOrder->id}", [
                    'expected_cents' => $expectedAmount,
                    'received_cents' => $amountTotal,
                    'order_id' => $lockedOrder->id,
                ]);

                return false;
            }

            $rawId = (string) ($sessionData['id'] ?? '');
            $paymentIntentId = $sessionData['payment_intent']
                ?? (str_starts_with($rawId, 'pi_') ? $rawId : null)
                ?? $lockedOrder->stripe_payment_intent_id;

            $sessionId = str_starts_with($rawId, 'cs_')
                ? $rawId
                : $lockedOrder->stripe_session_id;

            $chargeId = str_starts_with($rawId, 'ch_')
                ? $rawId
                : ($sessionData['latest_charge'] ?? $sessionData['charge'] ?? $lockedOrder->stripe_charge_id);

            // Connect guest order to registered user account if matching email exists
            $userId = $lockedOrder->user_id;
            if ($userId === null && ! empty($lockedOrder->customer_email)) {
                $existingUser = User::where('email', $lockedOrder->customer_email)->first();
                if ($existingUser) {
                    $userId = $existingUser->id;
                }
            }

            // Enrich customer contact details from Stripe customer_details if missing
            $customerDetails = is_array($sessionData['customer_details'] ?? null)
                ? $sessionData['customer_details']
                : [];

            $customerName = $lockedOrder->customer_name;
            if (empty($customerName) && ! empty($customerDetails['name'])) {
                $customerName = (string) $customerDetails['name'];
            }

            $customerPhone = $lockedOrder->customer_phone;
            if (empty($customerPhone) && ! empty($customerDetails['phone'])) {
                $customerPhone = (string) $customerDetails['phone'];
            }

            $orderUpdates = [
                'payment_status' => PaymentStatus::Paid,
                'status' => OrderStatus::Processing,
                'paid_at' => now(),
                'stripe_payment_intent_id' => $paymentIntentId,
                'stripe_session_id' => $sessionId,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
            ];

            if ($userId !== null) {
                $orderUpdates['user_id'] = $userId;
            }

            if (! empty($chargeId)) {
                $orderUpdates['stripe_charge_id'] = $chargeId;
            }

            $lockedOrder->update($orderUpdates);

            // Update or create Payment record
            $paymentUpdates = [
                'stripe_session_id' => $sessionId,
                'stripe_payment_intent_id' => $paymentIntentId,
                'status' => PaymentStatus::Paid,
                'amount' => $lockedOrder->total,
                'currency' => $lockedOrder->currency,
                'paid_at' => now(),
            ];

            if (! empty($chargeId)) {
                $paymentUpdates['stripe_charge_id'] = $chargeId;
            }

            Payment::updateOrCreate(
                ['order_id' => $lockedOrder->id, 'driver' => 'stripe'],
                $paymentUpdates
            );

            Log::info("Order {$lockedOrder->number} ({$lockedOrder->id}) marked as paid via Stripe.");

            Mail::to($lockedOrder->customer_email)->queue(
                new OrderConfirmationMail($lockedOrder)
            );

            return true;
        });
    }
}
