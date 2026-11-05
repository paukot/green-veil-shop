<?php

namespace App\Payments\Stripe;

use App\Models\Order;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\DTOs\RefundResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

class StripeGateway implements PaymentGateway
{
    public function __construct(
        protected StripeClient $client,
    ) {}

    public function createCheckout(Order $order): CheckoutResult
    {
        try {
            $order->loadMissing(['items', 'deliveryOption']);

            if ($order->items->isEmpty()) {
                return CheckoutResult::failed('Cannot create Stripe checkout session for an order without items.');
            }

            $currency = strtolower($order->currency ?: 'eur');
            $lineItems = [];

            $discountRatio = ($order->subtotal > 0 && $order->discount_amount > 0)
                ? max(0, ($order->subtotal - $order->discount_amount) / $order->subtotal)
                : 1.0;

            $itemsTotalCents = 0;

            foreach ($order->items as $item) {
                $unitCents = (int) round($item->unit_price * $discountRatio * 100);
                $productData = ['name' => $item->product_name];

                if (! empty($item->product_sku)) {
                    $productData['description'] = "SKU: {$item->product_sku}";
                }

                $lineItems[] = [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => $productData,
                        'unit_amount' => $unitCents,
                    ],
                    'quantity' => (int) $item->quantity,
                ];

                $itemsTotalCents += ($unitCents * (int) $item->quantity);
            }

            $deliveryCents = (int) round($order->delivery_amount * 100);
            if ($deliveryCents > 0) {
                $deliveryName = $order->deliveryOption->name ?? 'Standard Delivery';
                $lineItems[] = [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => "Shipping ({$deliveryName})",
                        ],
                        'unit_amount' => $deliveryCents,
                    ],
                    'quantity' => 1,
                ];
            }

            // Adjust any 1-cent discrepancy caused by percentage discount rounding
            $targetTotalCents = (int) round($order->total * 100);
            $currentTotalCents = $itemsTotalCents + $deliveryCents;
            $diff = $targetTotalCents - $currentTotalCents;

            if ($diff !== 0 && count($lineItems) > 0) {
                $lineItems[0]['price_data']['unit_amount'] += $diff;
            }

            $params = [
                'mode' => 'payment',
                'customer_email' => $order->customer_email,
                'client_reference_id' => (string) $order->id,
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'public_id' => $order->public_id,
                    'order_number' => $order->number,
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'public_id' => $order->public_id,
                        'order_number' => $order->number,
                    ],
                    'description' => "Order {$order->number}",
                ],
                'line_items' => $lineItems,
                'success_url' => url('/checkout/success?order='.$order->public_id),
                'cancel_url' => url('/checkout/cancel?order='.$order->public_id),
                'expires_at' => time() + (31 * 60),
            ];

            $session = $this->client->checkout->sessions->create($params, [
                'idempotency_key' => "order_{$order->public_id}",
            ]);

            return CheckoutResult::success(
                redirectUrl: $session->url,
                sessionId: $session->id,
            );
        } catch (ApiErrorException $e) {
            Log::error('Stripe Checkout session creation failed: '.$e->getMessage(), [
                'order_id' => $order->id,
                'public_id' => $order->public_id,
            ]);

            return CheckoutResult::failed($e->getMessage());
        } catch (Throwable $e) {
            Log::error('Unexpected error during Stripe checkout creation: '.$e->getMessage(), [
                'order_id' => $order->id,
            ]);

            return CheckoutResult::failed('An unexpected error occurred while preparing your payment session.');
        }
    }

    public function refund(Model $payment, ?int $amountInCents = null, ?string $reason = null): RefundResult
    {
        try {
            $order = $payment instanceof Order ? $payment : ($payment->order ?? null);
            $paymentIntentId = $order->stripe_payment_intent_id
                ?? ($payment->stripe_payment_intent_id ?? null);
            $chargeId = $order?->stripe_charge_id;

            $params = [];
            if ($paymentIntentId) {
                $params['payment_intent'] = $paymentIntentId;
            } elseif ($chargeId) {
                $params['charge'] = $chargeId;
            } else {
                return RefundResult::failed('No Stripe payment intent or charge ID found to refund.');
            }

            if ($amountInCents !== null) {
                $params['amount'] = $amountInCents;
            }

            if ($reason && in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'], true)) {
                $params['reason'] = $reason;
            }

            if ($order) {
                $params['metadata'] = [
                    'order_id' => (string) $order->id,
                    'public_id' => (string) $order->public_id,
                    'order_number' => (string) $order->number,
                ];
            }

            $refund = $this->client->refunds->create($params);

            return RefundResult::success($refund->id);
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed: '.$e->getMessage(), [
                'payment_id' => $payment->getKey(),
                'amount_cents' => $amountInCents,
            ]);

            return RefundResult::failed($e->getMessage());
        } catch (Throwable $e) {
            Log::error('Unexpected error during Stripe refund: '.$e->getMessage());

            return RefundResult::failed('An unexpected error occurred while processing the refund.');
        }
    }
}
