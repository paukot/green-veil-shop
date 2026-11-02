<?php

namespace App\Http\Controllers;

use App\Actions\Checkout\MarkOrderPaid;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentManager;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\StripeClient;

class CheckoutResultController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected StripeClient $stripeClient,
        protected MarkOrderPaid $markOrderPaid,
    ) {}

    public function success(Request $request): Response|RedirectResponse
    {
        $publicId = $request->query('order');

        if (! $publicId) {
            return redirect()->route('home');
        }

        $order = Order::query()
            ->where('public_id', $publicId)
            ->with(['items', 'deliveryOption', 'paymentOption', 'payments'])
            ->first();

        if (! $order) {
            return redirect()->route('home');
        }

        // If order belongs to an authenticated user, don't show it to another logged-in user
        if ($order->user_id && $request->user() && $request->user()->id !== $order->user_id) {
            abort(403);
        }

        // If Stripe payment is still pending, synchronously reconcile with Stripe API
        if (
            $order->payment_status !== PaymentStatus::Paid
            && ! empty($order->stripe_session_id)
            && ($order->paymentOption?->driver === 'stripe')
        ) {
            try {
                $session = $this->stripeClient->checkout->sessions->retrieve($order->stripe_session_id);
                if (($session->payment_status ?? null) === 'paid') {
                    $this->markOrderPaid->execute($order, $session->toArray());
                    $order->refresh();
                }
            } catch (\Throwable $e) {
                Log::warning('Unable to reconcile Stripe checkout session on success page: '.$e->getMessage(), [
                    'order_id' => $order->id,
                    'stripe_session_id' => $order->stripe_session_id,
                ]);
            }
        }

        // Clear cart session if not yet cleared
        $this->cartService->clearCart($request);

        return Inertia::render('Checkout/Success', [
            'order' => OrderResource::make($order),
        ]);
    }

    public function cancel(Request $request): Response|RedirectResponse
    {
        $publicId = $request->query('order');

        if (! $publicId) {
            return redirect()->route('cart.index');
        }

        $order = Order::query()->where('public_id', $publicId)->first();

        if (! $order) {
            return redirect()->route('cart.index');
        }

        return Inertia::render('Checkout/Cancel', [
            'order' => [
                'public_id' => $order->public_id,
                'number' => $order->number,
                'total' => (float) $order->total,
                'currency' => $order->currency,
            ],
        ]);
    }

    public function retry(
        Request $request,
        string $publicId,
        PaymentManager $paymentManager
    ): \Symfony\Component\HttpFoundation\Response {
        $order = Order::query()
            ->where('public_id', $publicId)
            ->where('status', OrderStatus::PendingPayment)
            ->where('payment_status', PaymentStatus::Unpaid)
            ->first();

        if (! $order) {
            return redirect()->route('checkout.show')->withErrors([
                'payment' => 'This order is not eligible for payment retry.',
            ]);
        }

        if ($order->user_id && $request->user() && $request->user()->id !== $order->user_id) {
            abort(403);
        }

        $driver = $order->paymentOption->driver ?? 'stripe';
        $gateway = $paymentManager->gateway($driver);
        $result = $gateway->createCheckout($order);

        if (! $result->successful) {
            return redirect()->route('checkout.cancel', ['order' => $order->public_id])->withErrors([
                'payment' => $result->errorMessage ?? 'Unable to create payment session.',
            ]);
        }

        if ($result->sessionId) {
            $order->update(['stripe_session_id' => $result->sessionId]);

            Payment::updateOrCreate(
                ['order_id' => $order->id, 'driver' => $driver],
                [
                    'stripe_session_id' => $result->sessionId,
                    'status' => PaymentStatus::Unpaid,
                    'amount' => $order->total,
                    'currency' => $order->currency,
                ]
            );
        }

        if ($result->redirectUrl) {
            return Inertia::location($result->redirectUrl);
        }

        return redirect()->route('checkout.success', ['order' => $order->public_id]);
    }
}
