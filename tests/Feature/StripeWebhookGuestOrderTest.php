<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StripeEvent;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Stripe\WebhookSignature;

function signStripeWebhookPayload(string $payload, string $secret): string
{
    return WebhookSignature::generateSignatureHeader($payload, $secret);
}

beforeEach(function () {
    Config::set('services.stripe.webhook_secret', 'whsec_test_secret_for_suite');
});

test('guest checkout session completed marks order paid, assigns order_id to stripe event, and enriches contact details', function () {
    $secret = 'whsec_test_secret_for_suite';

    $order = Order::factory()->create([
        'user_id' => null,
        'customer_name' => 'Original Guest Name',
        'customer_phone' => null,
        'customer_email' => 'guest@example.com',
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => 54.50,
        'currency' => 'EUR',
    ]);

    $payloadArray = [
        'id' => 'evt_guest_checkout_completed',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_guest_session',
                'object' => 'checkout.session',
                'payment_intent' => 'pi_test_guest_intent',
                'payment_status' => 'paid',
                'amount_total' => 5450,
                'currency' => 'eur',
                'client_reference_id' => (string) $order->id,
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'public_id' => $order->public_id,
                ],
                'customer_details' => [
                    'email' => 'guest@example.com',
                    'name' => 'Verified Guest Name',
                    'phone' => '+37060012345',
                ],
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripeWebhookPayload($payload, $secret);

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
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    expect($order->stripe_payment_intent_id)->toBe('pi_test_guest_intent');
    expect($order->customer_phone)->toBe('+37060012345');

    $eventRecord = StripeEvent::where('stripe_event_id', 'evt_guest_checkout_completed')->first();
    expect($eventRecord)->not->toBeNull();
    expect($eventRecord->order_id)->toBe($order->id);
    expect($eventRecord->processed_at)->not->toBeNull();
});

test('guest checkout session completed auto-links order to existing registered user with matching email', function () {
    $secret = 'whsec_test_secret_for_suite';

    $existingUser = User::factory()->create([
        'email' => 'registered_customer@example.com',
    ]);

    $order = Order::factory()->create([
        'user_id' => null,
        'customer_email' => 'registered_customer@example.com',
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => 80.00,
        'currency' => 'EUR',
    ]);

    $payloadArray = [
        'id' => 'evt_guest_matches_user',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_matches_user',
                'object' => 'checkout.session',
                'payment_intent' => 'pi_test_matches_user',
                'payment_status' => 'paid',
                'amount_total' => 8000,
                'currency' => 'eur',
                'metadata' => [
                    'order_id' => (string) $order->id,
                ],
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripeWebhookPayload($payload, $secret);

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
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    expect($order->user_id)->toBe($existingUser->id);
});

test('payment_intent.succeeded webhook event resolves guest order via payment_intent_data metadata and marks it paid', function () {
    $secret = 'whsec_test_secret_for_suite';

    $order = Order::factory()->create([
        'user_id' => null,
        'customer_email' => 'pi_guest@example.com',
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => 32.00,
        'currency' => 'EUR',
    ]);

    $payloadArray = [
        'id' => 'evt_pi_succeeded_test',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_intent_succeeded_alone',
                'object' => 'payment_intent',
                'amount' => 3200,
                'currency' => 'eur',
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'public_id' => $order->public_id,
                ],
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripeWebhookPayload($payload, $secret);

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
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    expect($order->stripe_payment_intent_id)->toBe('pi_test_intent_succeeded_alone');

    $eventRecord = StripeEvent::where('stripe_event_id', 'evt_pi_succeeded_test')->first();
    expect($eventRecord)->not->toBeNull();
    expect($eventRecord->order_id)->toBe($order->id);
});

test('charge.refunded webhook resolves guest order via payment record and updates status', function () {
    $secret = 'whsec_test_secret_for_suite';

    $order = Order::factory()->create([
        'user_id' => null,
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'total' => 45.00,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'stripe_payment_intent_id' => 'pi_guest_refund_pi',
        'status' => PaymentStatus::Paid,
        'amount' => 45.00,
    ]);

    $payloadArray = [
        'id' => 'evt_guest_charge_refunded',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_guest_charge_refunded',
                'object' => 'charge',
                'payment_intent' => 'pi_guest_refund_pi',
                'amount' => 4500,
                'amount_refunded' => 4500,
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripeWebhookPayload($payload, $secret);

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
    expect($order->payment_status)->toBe(PaymentStatus::Refunded);
    expect($order->status)->toBe(OrderStatus::Refunded);

    $eventRecord = StripeEvent::where('stripe_event_id', 'evt_guest_charge_refunded')->first();
    expect($eventRecord->order_id)->toBe($order->id);
});

test('guest order fallback recovers order matching customer_email and total when session metadata is absent', function () {
    $secret = 'whsec_test_secret_for_suite';

    $order = Order::factory()->create([
        'user_id' => null,
        'customer_email' => 'orphan_guest@example.com',
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => 77.00,
        'currency' => 'EUR',
    ]);

    // Payload with completely missing metadata and unknown session id
    $payloadArray = [
        'id' => 'evt_recovered_by_email',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_untracked_session_123',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'amount_total' => 7700,
                'currency' => 'eur',
                'customer_details' => [
                    'email' => 'orphan_guest@example.com',
                ],
            ],
        ],
    ];

    $payload = json_encode($payloadArray);
    $signature = signStripeWebhookPayload($payload, $secret);

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
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    expect($order->status)->toBe(OrderStatus::Processing);

    $eventRecord = StripeEvent::where('stripe_event_id', 'evt_recovered_by_email')->first();
    expect($eventRecord->order_id)->toBe($order->id);
});
