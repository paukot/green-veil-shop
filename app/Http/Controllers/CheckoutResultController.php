<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentManager;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutResultController extends Controller
{
    public function __construct(
        protected CartService $cartService,
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

        // If order belongs to an authenticated user, don't show it to another logged in user
        if ($order->user_id && $request->user() && $request->user()->id !== $order->user_id) {
            abort(403);
        }

        // Clear cart session if not yet cleared
        $this->cartService->clearCart($request);

        return Inertia::render('Checkout/Success', [
            'order' => [
                'public_id' => $order->public_id,
                'number' => $order->number,
                'status' => $order->status->value,
                'payment_status' => $order->payment_status->value,
                'subtotal' => (float) $order->subtotal,
                'delivery_amount' => (float) $order->delivery_amount,
                'discount_amount' => (float) $order->discount_amount,
                'tax_amount' => (float) $order->tax_amount,
                'total' => (float) $order->total,
                'currency' => $order->currency,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'shipping_address' => $order->shipping_address,
                'billing_address' => $order->billing_address,
                'placed_at' => $order->placed_at?->toIso8601String(),
                'paid_at' => $order->paid_at?->toIso8601String(),
            ],
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'product_image' => $item->product_image,
                'unit_price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ])->values(),
            'deliveryOption' => $order->deliveryOption ? [
                'name' => $order->deliveryOption->name,
                'code' => $order->deliveryOption->code,
                'estimated_delivery_label' => $order->deliveryOption->estimatedDeliveryLabel(),
            ] : null,
            'paymentOption' => $order->paymentOption ? [
                'name' => $order->paymentOption->name,
                'code' => $order->paymentOption->code,
                'driver' => $order->paymentOption->driver,
                'description' => $order->paymentOption->description,
                'instructions' => $order->paymentOption->instructions,
            ] : null,
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

        $driver = $order->paymentOption?->driver ?? 'stripe';
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
