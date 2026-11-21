<?php

namespace App\Http\Controllers;

use App\Jobs\HandleStripeWebhook;
use App\Models\StripeEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = (string) $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature');
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            Log::error('Stripe webhook received but services.stripe.webhook_secret is not configured.');

            // A 5xx makes Stripe retry the delivery once the secret is configured.
            return response()->json(['error' => 'Webhook not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (UnexpectedValueException $e) {
            Log::warning('Invalid Stripe webhook payload: '.$e->getMessage());

            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::warning('Invalid Stripe webhook signature: '.$e->getMessage());

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // createOrFirst relies on the unique stripe_event_id, so two concurrent deliveries
        // of one event resolve to the same record instead of a unique-violation 500.
        $eventRecord = StripeEvent::query()->createOrFirst(
            ['stripe_event_id' => $event->id],
            [
                'type' => $event->type,
                'payload' => json_decode($payload, true) ?: [],
            ],
        );

        if ($eventRecord->processed_at !== null) {
            return response()->json(['status' => 'already_processed'], 200);
        }

        HandleStripeWebhook::dispatch($eventRecord->id);

        return response()->json(['status' => 'received'], 200);
    }
}
