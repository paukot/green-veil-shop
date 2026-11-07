<?php

namespace App\Actions\Checkout;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Throwable;

class ReconcileStripeCheckoutSession
{
    public function __construct(
        protected StripeClient $stripeClient,
        protected MarkOrderPaid $markOrderPaid,
    ) {}

    /**
     * Reconcile a pending Stripe order with the Stripe Checkout Session status.
     */
    public function execute(Order $order): void
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            return;
        }

        if (empty($order->stripe_session_id) || ($order->paymentOption?->driver !== 'stripe')) {
            return;
        }

        try {
            $session = $this->stripeClient->checkout->sessions->retrieve($order->stripe_session_id);
            if (($session->payment_status ?? null) === 'paid') {
                $this->markOrderPaid->execute($order, $session->toArray());
                $order->refresh();
            }
        } catch (Throwable $e) {
            Log::warning('Unable to reconcile Stripe checkout session on success page: '.$e->getMessage(), [
                'order_id' => $order->id,
                'stripe_session_id' => $order->stripe_session_id,
            ]);
        }
    }
}
