<?php

namespace App\Http\Controllers;

use App\Actions\Checkout\ReconcileStripeCheckoutSession;
use App\Actions\Checkout\RecordPaymentSession;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Payments\PaymentManager;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckoutResultController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected ReconcileStripeCheckoutSession $reconcileStripeSession,
        protected RecordPaymentSession $recordPaymentSession,
    ) {}

    public function success(Request $request): Response|RedirectResponse
    {
        $publicId = $request->string('order')->value();
        if (empty($publicId)) {
            return to_route('home');
        }

        $order = Order::query()
            ->where('public_id', $publicId)
            ->with(['items', 'deliveryOption', 'paymentOption', 'payments'])
            ->first();

        if (! $order) {
            return to_route('home');
        }

        abort_if($order->isOwnedByAnotherUser($request->user()), 403);

        $this->reconcileStripeSession->execute($order);
        $this->cartService->clearCart($request);

        return Inertia::render('Checkout/Success', [
            'order' => OrderResource::make($order),
        ]);
    }

    public function cancel(Request $request): Response|RedirectResponse
    {
        $publicId = $request->string('order')->value();
        if (empty($publicId)) {
            return to_route('cart.index');
        }

        $order = Order::query()->where('public_id', $publicId)->first();
        if (! $order) {
            return to_route('cart.index');
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
    ): SymfonyResponse {
        $order = Order::query()
            ->where('public_id', $publicId)
            ->where('status', OrderStatus::PendingPayment)
            ->where('payment_status', PaymentStatus::Unpaid)
            ->first();

        if (! $order) {
            return to_route('checkout.show')->withErrors([
                'payment' => 'This order is not eligible for payment retry.',
            ]);
        }

        abort_if($order->isOwnedByAnotherUser($request->user()), 403);

        $driver = $order->paymentOption->driver ?? 'stripe';
        $gateway = $paymentManager->gateway($driver);
        $result = $gateway->createCheckout($order);

        if (! $result->successful) {
            return to_route('checkout.cancel', ['order' => $order->public_id])->withErrors([
                'payment' => $result->errorMessage ?? 'Unable to create payment session.',
            ]);
        }

        $this->recordPaymentSession->execute($order, $driver, $result->sessionId);

        if ($result->redirectUrl) {
            return Inertia::location($result->redirectUrl);
        }

        return to_route('checkout.success', ['order' => $order->public_id]);
    }
}
