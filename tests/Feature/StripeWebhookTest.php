<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StripeEvent;
use Illuminate\Support\Facades\Config;
use Stripe\WebhookSignature;

function signStripePayload(string $payload, string $secret): string
{
    return WebhookSignature::generateSignatureHeader($payload, $secret);
}

beforeEach(function () {
    Config::set('services.stripe.webhook_secret', 'whsec_test_secret_for_suite');
});

test('rejects unsigned webhook request with 400', function () {
    $response = $this->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode(['id' => 'evt_123', 'type' => 'test'])
    );

    $response->assertStatus(400);
});

test('rejects webhook request with invalid signature with 400', function () {
    $payload = json_encode(['id' => 'evt_invalid', 'type' => 'test']);

    $response = $this->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=12345,v1=bad_signature',
        ],
        $payload
    );

    $response->assertStatus(400);
});

test('checkout.session.completed marks order and payment as paid', function () {
    $secret = 'whsec_test_secret_for_suite';

    $order = Order::factory()->create([
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => 65.00,
        'currency' => 'EUR',
    ]);

    $payloadArray = [
        'id' => 'evt_checkout_completed_1',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_session_completed_1',
                'payment_intent' => 'pi_test_intent_1',
                'payment_status' => 'paid',
                'amount_total' => 6500,
                'currency' => 'eur',
                'client_reference_id' => (string) $order->id,
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'public_id' => $order->public_id,
                ],
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripePayload($payload, $secret);

    $response = $this->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    $response->assertOk();
    $response->assertJson(['status' => 'received']);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Processing);
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    expect($order->stripe_payment_intent_id)->toBe('pi_test_intent_1');
    expect($order->paid_at)->not->toBeNull();

    $payment = Payment::where('order_id', $order->id)->where('driver', 'stripe')->first();
    expect($payment)->not->toBeNull();
    expect($payment->status)->toBe(PaymentStatus::Paid);
    expect($payment->stripe_payment_intent_id)->toBe('pi_test_intent_1');

    $eventRecord = StripeEvent::where('stripe_event_id', 'evt_checkout_completed_1')->first();
    expect($eventRecord)->not->toBeNull();
    expect($eventRecord->processed_at)->not->toBeNull();
});

test('replayed webhook event is idempotent and does not re-process', function () {
    $secret = 'whsec_test_secret_for_suite';

    $order = Order::factory()->create([
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => 40.00,
        'currency' => 'EUR',
    ]);

    $payloadArray = [
        'id' => 'evt_replayed_1',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_replayed_1',
                'payment_intent' => 'pi_test_replayed_1',
                'payment_status' => 'paid',
                'amount_total' => 4000,
                'currency' => 'eur',
                'metadata' => [
                    'order_id' => (string) $order->id,
                ],
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripePayload($payload, $secret);

    // First call
    $firstResponse = $this->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    $firstResponse->assertOk();
    $firstResponse->assertJson(['status' => 'received']);

    // Replayed second call with identical event id
    $secondResponse = $this->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    $secondResponse->assertOk();
    $secondResponse->assertJson(['status' => 'already_processed']);
});

test('does not mark order as paid if amount does not match', function () {
    $secret = 'whsec_test_secret_for_suite';

    $order = Order::factory()->create([
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => 100.00, // 10,000 cents
        'currency' => 'EUR',
    ]);

    // Send tampered amount of 100 cents (1 EUR)
    $payloadArray = [
        'id' => 'evt_tampered_amount',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_tampered',
                'payment_status' => 'paid',
                'amount_total' => 100, // 1.00 EUR instead of 100.00 EUR
                'currency' => 'eur',
                'metadata' => [
                    'order_id' => (string) $order->id,
                ],
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripePayload($payload, $secret);

    $response = $this->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    $response->assertOk();

    $order->refresh();
    // Must NOT be marked as paid
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);
    expect($order->status)->toBe(OrderStatus::PendingPayment);
});

test('checkout.session.async_payment_failed marks order payment failed and releases stock', function () {
    $secret = 'whsec_test_secret_for_suite';

    $product = Product::factory()->create([
        'stock_quantity' => 5,
        'track_inventory' => true,
    ]);

    $order = Order::factory()->create([
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $payloadArray = [
        'id' => 'evt_async_failed',
        'type' => 'checkout.session.async_payment_failed',
        'data' => [
            'object' => [
                'id' => 'cs_async_failed',
                'metadata' => [
                    'order_id' => (string) $order->id,
                ],
                'last_payment_error' => [
                    'message' => 'Insufficient funds in customer bank account',
                ],
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripePayload($payload, $secret);

    $response = $this->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    $response->assertOk();

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Failed);

    // Stock released: 5 + 3 = 8
    expect($product->fresh()->stock_quantity)->toBe(8);
});

test('checkout.session.expired cancels order and releases stock', function () {
    $secret = 'whsec_test_secret_for_suite';

    $product = Product::factory()->create([
        'stock_quantity' => 10,
        'track_inventory' => true,
    ]);

    $order = Order::factory()->create([
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 4,
    ]);

    $payloadArray = [
        'id' => 'evt_expired',
        'type' => 'checkout.session.expired',
        'data' => [
            'object' => [
                'id' => 'cs_expired',
                'metadata' => [
                    'order_id' => (string) $order->id,
                ],
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripePayload($payload, $secret);

    $response = $this->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    $response->assertOk();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled);
    expect($order->payment_status)->toBe(PaymentStatus::Expired);

    // Stock released: 10 + 4 = 14
    expect($product->fresh()->stock_quantity)->toBe(14);
});

test('charge.refunded updates order and payment status to refunded', function () {
    $secret = 'whsec_test_secret_for_suite';

    $order = Order::factory()->create([
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'stripe_payment_intent_id' => 'pi_refund_test',
        'total' => 50.00,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'stripe_payment_intent_id' => 'pi_refund_test',
        'status' => PaymentStatus::Paid,
        'amount' => 50.00,
    ]);

    $payloadArray = [
        'id' => 'evt_charge_refunded',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_refund_test',
                'payment_intent' => 'pi_refund_test',
                'amount' => 5000,
                'amount_refunded' => 5000,
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripePayload($payload, $secret);

    $response = $this->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    $response->assertOk();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Refunded);
    expect($order->payment_status)->toBe(PaymentStatus::Refunded);

    $payment = Payment::where('order_id', $order->id)->first();
    expect($payment->status)->toBe(PaymentStatus::Refunded);
});

test('charge.refunded with partial amount updates status to partially_refunded', function () {
    $secret = 'whsec_test_secret_for_suite';

    $order = Order::factory()->create([
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'stripe_payment_intent_id' => 'pi_partial_refund_test',
        'total' => 100.00,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'stripe_payment_intent_id' => 'pi_partial_refund_test',
        'status' => PaymentStatus::Paid,
        'amount' => 100.00,
    ]);

    $payloadArray = [
        'id' => 'evt_charge_partial_refunded',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_partial_refund_test',
                'payment_intent' => 'pi_partial_refund_test',
                'amount' => 10000,
                'amount_refunded' => 4000,
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripePayload($payload, $secret);

    $response = $this->call(
        'POST',
        route('stripe.webhook'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ],
        $payload
    );

    $response->assertOk();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Processing);
    expect($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded);

    $payment = Payment::where('order_id', $order->id)->first();
    expect($payment->status)->toBe(PaymentStatus::PartiallyRefunded);
});
