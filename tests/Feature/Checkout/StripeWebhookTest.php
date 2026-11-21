<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StripeEvent;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Stripe\WebhookSignature;

function signStripeWebhookPayload(string $payload, string $secret): string
{
    return WebhookSignature::generateSignatureHeader($payload, $secret);
}

/**
 * Deliver a signed Stripe event to the webhook endpoint.
 *
 * @param  array<string, mixed>  $object
 */
function deliverStripeEvent(string $eventId, string $type, array $object): TestResponse
{
    $payload = (string) json_encode([
        'id' => $eventId,
        'type' => $type,
        'data' => ['object' => $object],
    ]);

    return test()->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => signStripeWebhookPayload($payload, 'whsec_test_secret_for_suite'),
    ], $payload);
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

    describe('Configuration', function () {
        it('returns 500 so Stripe retries when no webhook secret is configured', function () {
            Config::set('services.stripe.webhook_secret', null);

            $response = deliverStripeEvent('evt_unconfigured', 'checkout.session.completed', []);

            $response->assertInternalServerError();
            expect(StripeEvent::count())->toBe(0);
        });
    });

    describe('Reservation release', function () {
        it('keeps stock reserved when a card is declined inside an open checkout session', function () {
            $product = Product::factory()->create(['stock_quantity' => 1, 'track_inventory' => true]);
            $order = Order::factory()->pendingPayment()->create();
            OrderItem::factory()->for($order)->forProduct($product, 2)->create();

            deliverStripeEvent('evt_declined', 'payment_intent.payment_failed', [
                'id' => 'pi_declined',
                'object' => 'payment_intent',
                'metadata' => ['order_id' => (string) $order->id],
            ])->assertOk();

            $order->refresh();
            expect($order->payment_status)->toBe(PaymentStatus::Failed);
            expect($order->status)->toBe(OrderStatus::PendingPayment);
            expect($product->fresh()->stock_quantity)->toBe(1);
        });

        it('releases stock once when an async failure is followed by session expiry', function () {
            $product = Product::factory()->create(['stock_quantity' => 1, 'track_inventory' => true]);
            $order = Order::factory()->pendingPayment()->create();
            OrderItem::factory()->for($order)->forProduct($product, 2)->create();
            $session = [
                'id' => 'cs_async_failed',
                'object' => 'checkout.session',
                'metadata' => ['order_id' => (string) $order->id],
            ];

            deliverStripeEvent('evt_async_failed', 'checkout.session.async_payment_failed', $session)->assertOk();
            deliverStripeEvent('evt_session_expired', 'checkout.session.expired', $session)->assertOk();

            expect($product->fresh()->stock_quantity)->toBe(3);
            expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
        });

        it('does not restock again when Stripe confirms a refund the admin already restocked', function () {
            $product = Product::factory()->create(['stock_quantity' => 5, 'track_inventory' => true]);
            $order = Order::factory()->paid()->reservationsReleased()->create([
                'total' => 40.00,
                'stripe_payment_intent_id' => 'pi_refunded',
            ]);
            OrderItem::factory()->for($order)->forProduct($product, 2)->create();

            deliverStripeEvent('evt_refunded', 'charge.refunded', [
                'id' => 'ch_refunded',
                'object' => 'charge',
                'payment_intent' => 'pi_refunded',
                'amount' => 4000,
                'amount_refunded' => 4000,
            ])->assertOk();

            $order->refresh();
            expect($order->payment_status)->toBe(PaymentStatus::Refunded);
            expect((float) $order->refunded_amount)->toBe(40.0);
            expect($product->fresh()->stock_quantity)->toBe(5);
        });
    });

    describe('Payment guards', function () {
        it('records a payment that arrives after release without moving the order to processing', function () {
            $order = Order::factory()->cancelled()->reservationsReleased()->create([
                'payment_status' => PaymentStatus::Expired,
                'total' => 50.00,
                'currency' => 'EUR',
            ]);

            deliverStripeEvent('evt_late_payment', 'checkout.session.completed', [
                'id' => 'cs_late',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'amount_total' => 5000,
                'currency' => 'eur',
                'metadata' => ['order_id' => (string) $order->id],
            ])->assertOk();

            $order->refresh();
            expect($order->payment_status)->toBe(PaymentStatus::Paid);
            expect($order->status)->toBe(OrderStatus::Cancelled);
        });

        it('refuses to mark an order paid when the event carries no amount', function () {
            $order = Order::factory()->pendingPayment()->create(['total' => 50.00, 'currency' => 'EUR']);

            deliverStripeEvent('evt_no_amount', 'payment_intent.succeeded', [
                'id' => 'pi_no_amount',
                'object' => 'payment_intent',
                'currency' => 'eur',
                'metadata' => ['order_id' => (string) $order->id],
            ])->assertOk();

            expect($order->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
        });

        it('attaches a guest order only to an account that verified the email', function () {
            User::factory()->unverified()->create(['email' => 'claimant@example.com']);
            $order = Order::factory()->guest()->pendingPayment()->create([
                'customer_email' => 'claimant@example.com',
                'total' => 50.00,
                'currency' => 'EUR',
            ]);

            deliverStripeEvent('evt_guest_paid', 'checkout.session.completed', [
                'id' => 'cs_guest_paid',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'amount_total' => 5000,
                'currency' => 'eur',
                'metadata' => ['order_id' => (string) $order->id],
            ])->assertOk();

            expect($order->fresh()->user_id)->toBeNull();
        });
    });
});
