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
        $secret = config('services.stripe.webhook_secret') ?: 'whsec_placeholder';

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (UnexpectedValueException $e) {
            Log::warning('Invalid Stripe webhook payload: '.$e->getMessage());

            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::warning('Invalid Stripe webhook signature: '.$e->getMessage());

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Check if event already exists for idempotency
        $existingEvent = StripeEvent::where('stripe_event_id', $event->id)->first();

        if ($existingEvent && $existingEvent->processed_at !== null) {
            return response()->json(['status' => 'already_processed'], 200);
        }

        $eventRecord = $existingEvent ?? StripeEvent::create([
            'stripe_event_id' => $event->id,
            'type' => $event->type,
            'payload' => json_decode($payload, true) ?: [],
        ]);

        if (app()->environment('testing')) {
            HandleStripeWebhook::dispatchSync($eventRecord->id);
        } else {
            HandleStripeWebhook::dispatch($eventRecord->id);
        }

        return response()->json(['status' => 'received'], 200);
    }
}
