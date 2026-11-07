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

describe('Stripe Webhook', function () {
    beforeEach(function () {
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret_for_suite');
    });

    describe('Signature & Payload Validation', function () {
        it('returns 400 when webhook signature header is missing', function () {
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
            $response->assertJson(['error' => 'Invalid signature']);
        });

        it('returns 400 when webhook signature is invalid', function () {
            $payload = json_encode(['id' => 'evt_invalid', 'type' => 'test']);

            $response = $this->call(
                'POST',
                route('stripe.webhook'),
                [],
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_STRIPE_SIGNATURE' => 't=12345,v1=bad_signature_hash',
                ],
                $payload
            );

            $response->assertStatus(400);
            $response->assertJson(['error' => 'Invalid signature']);
        });

        it('returns 400 when webhook payload is malformed', function () {
            $secret = 'whsec_test_secret_for_suite';
            $malformedPayload = '{"id": "evt_malformed", broken json';
            $signature = WebhookSignature::generateSignatureHeader($malformedPayload, $secret);

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
                $malformedPayload
            );

            $response->assertStatus(400);
            $response->assertJson(['error' => 'Invalid payload']);
        });

        it('returns 200 and ignores unhandled event types gracefully', function () {
            $secret = 'whsec_test_secret_for_suite';
            $payload = json_encode([
                'id' => 'evt_unhandled_123',
                'type' => 'customer.created',
                'data' => ['object' => ['id' => 'cus_123']],
            ]);
            $signature = WebhookSignature::generateSignatureHeader($payload, $secret);

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
        });
    });

    describe('checkout.session.completed', function () {
        it('marks order and payment as paid for customer order', function () {
            $secret = 'whsec_test_secret_for_suite';

            $order = Order::factory()->create([
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
                'total' => 50.00,
                'currency' => 'EUR',
            ]);

            $payload = json_encode([
                'id' => 'evt_checkout_completed',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_test_session_abc',
                        'object' => 'checkout.session',
                        'payment_intent' => 'pi_test_intent_xyz',
                        'payment_status' => 'paid',
                        'amount_total' => 5000,
                        'currency' => 'eur',
                        'client_reference_id' => (string) $order->id,
                        'metadata' => [
                            'order_id' => (string) $order->id,
                            'public_id' => $order->public_id,
                        ],
                    ],
                ],
            ]);

            $signature = WebhookSignature::generateSignatureHeader($payload, $secret);

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

            $this->assertDatabaseHas('payments', [
                'order_id' => $order->id,
                'stripe_session_id' => 'cs_test_session_abc',
                'stripe_payment_intent_id' => 'pi_test_intent_xyz',
                'status' => PaymentStatus::Paid->value,
                'amount' => 50.00,
            ]);

            $this->assertDatabaseHas('stripe_events', [
                'stripe_event_id' => 'evt_checkout_completed',
                'type' => 'checkout.session.completed',
                'order_id' => $order->id,
            ]);
        });

        it('handles duplicate webhook event idempotently without creating duplicate records', function () {
            $secret = 'whsec_test_secret_for_suite';

            $order = Order::factory()->create([
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
                'total' => 45.00,
            ]);

            $payload = json_encode([
                'id' => 'evt_idempotent_test',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_test_idempotent',
                        'payment_intent' => 'pi_test_idempotent',
                        'payment_status' => 'paid',
                        'amount_total' => 4500,
                        'currency' => 'eur',
                        'client_reference_id' => (string) $order->id,
                        'metadata' => ['order_id' => (string) $order->id],
                    ],
                ],
            ]);

            $signature = WebhookSignature::generateSignatureHeader($payload, $secret);

            // First delivery
            $this->call('POST', route('stripe.webhook'), [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ], $payload)->assertOk();

            // Second delivery (replay)
            $response = $this->call('POST', route('stripe.webhook'), [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ], $payload);

            $response->assertOk();
            $response->assertJson(['status' => 'already_processed']);

            expect(Payment::where('order_id', $order->id)->count())->toBe(1);
            expect(StripeEvent::where('stripe_event_id', 'evt_idempotent_test')->count())->toBe(1);
        });

        it('marks guest order paid and enriches customer contact details from verified stripe session', function () {
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

            $payload = json_encode([
                'id' => 'evt_guest_checkout_completed',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_test_guest_session',
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
                            'name' => 'Original Guest Name',
                            'phone' => '+353871234567',
                        ],
                    ],
                ],
            ]);

            $signature = WebhookSignature::generateSignatureHeader($payload, $secret);

            $response = $this->call('POST', route('stripe.webhook'), [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ], $payload);

            $response->assertOk();

            $order->refresh();
            expect($order->payment_status)->toBe(PaymentStatus::Paid);
            expect($order->status)->toBe(OrderStatus::Processing);
            expect($order->customer_phone)->toBe('+353871234567');
        });

        it('associates guest order with existing user if email matches a registered customer account', function () {
            $secret = 'whsec_test_secret_for_suite';

            $user = User::factory()->create(['email' => 'registered@greenveil.test']);

            $order = Order::factory()->create([
                'user_id' => null,
                'customer_email' => 'registered@greenveil.test',
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
                'total' => 70.00,
            ]);

            $payload = json_encode([
                'id' => 'evt_guest_to_registered_user',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_test_guest_matched',
                        'payment_intent' => 'pi_test_guest_matched',
                        'payment_status' => 'paid',
                        'amount_total' => 7000,
                        'currency' => 'eur',
                        'client_reference_id' => (string) $order->id,
                        'metadata' => ['order_id' => (string) $order->id],
                    ],
                ],
            ]);

            $signature = WebhookSignature::generateSignatureHeader($payload, $secret);

            $this->call('POST', route('stripe.webhook'), [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ], $payload)->assertOk();

            $order->refresh();
            expect($order->user_id)->toBe($user->id);
            expect($order->payment_status)->toBe(PaymentStatus::Paid);
        });
    });

    describe('payment_intent events', function () {
        it('handles payment_intent.succeeded and marks order paid', function () {
            $secret = 'whsec_test_secret_for_suite';

            $order = Order::factory()->create([
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
                'total' => 60.00,
            ]);

            $payment = Payment::factory()->create([
                'order_id' => $order->id,
                'stripe_payment_intent_id' => 'pi_test_direct_intent',
                'status' => PaymentStatus::Unpaid,
                'amount' => 60.00,
            ]);

            $payload = json_encode([
                'id' => 'evt_intent_succeeded',
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'id' => 'pi_test_direct_intent',
                        'amount' => 6000,
                        'currency' => 'eur',
                        'metadata' => ['order_id' => (string) $order->id],
                    ],
                ],
            ]);

            $signature = WebhookSignature::generateSignatureHeader($payload, $secret);

            $response = $this->call('POST', route('stripe.webhook'), [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ], $payload);

            $response->assertOk();

            $payment->refresh();
            expect($payment->status)->toBe(PaymentStatus::Paid);

            $order->refresh();
            expect($order->payment_status)->toBe(PaymentStatus::Paid);
        });
    });
});
