<?php

use App\Models\StripeEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Stripe\WebhookSignature;

uses(RefreshDatabase::class);

describe('StripeWebhookController', function () {
    beforeEach(function () {
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret_for_suite');
    });

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

    it('stores StripeEvent and processes valid webhook event', function () {
        $secret = 'whsec_test_secret_for_suite';
        $eventId = 'evt_valid_'.uniqid();
        $payload = json_encode([
            'id' => $eventId,
            'type' => 'payment_intent.created',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
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

        $this->assertDatabaseHas('stripe_events', [
            'stripe_event_id' => $eventId,
            'type' => 'payment_intent.created',
        ]);
    });

    it('is idempotent and returns 200 without reprocessing if event was already processed', function () {
        $secret = 'whsec_test_secret_for_suite';
        $eventId = 'evt_processed_'.uniqid();

        // Create pre-processed event in database
        StripeEvent::create([
            'stripe_event_id' => $eventId,
            'type' => 'checkout.session.completed',
            'payload' => ['id' => $eventId],
            'processed_at' => now(),
        ]);

        $payload = json_encode([
            'id' => $eventId,
            'type' => 'checkout.session.completed',
            'data' => [],
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
        $response->assertJson(['status' => 'already_processed']);
    });
});
