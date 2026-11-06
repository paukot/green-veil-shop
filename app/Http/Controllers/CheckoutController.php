<?php

namespace App\Http\Controllers;

use App\Actions\Checkout\PlaceOrder;
use App\Actions\Checkout\ReleaseOrderStock;
use App\Enums\PaymentStatus;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\DeliveryOptionResource;
use App\Http\Resources\PaymentOptionResource;
use App\Mail\OrderConfirmationMail;
use App\Models\DeliveryOption;
use App\Models\Payment;
use App\Models\PaymentOption;
use App\Payments\PaymentManager;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
    ) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $cartItems = $this->cartService->getItems($request);
        $summary = $this->cartService->getSummaryFromCartItems($cartItems);

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('info', 'Your shopping bag is empty.');
        }

        $deliveryOptions = DeliveryOption::query()
            ->active()
            ->get();

        $paymentOptions = PaymentOption::query()
            ->active()
            ->orderBy('sort_order')
            ->get();

        $user = $request->user();

        return Inertia::render('Checkout/Index', [
            'items' => CartItemResource::collection($cartItems),
            'summary' => $summary,
            'deliveryOptions' => $deliveryOptions->map(fn ($deliveryOption) => DeliveryOptionResource::make($deliveryOption)
                ->withSubtotal($summary['subtotal'])
            ),
            'paymentOptions' => PaymentOptionResource::collection($paymentOptions),
            'customer' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
        ]);
    }

    public function store(
        CheckoutRequest $request,
        PlaceOrder $placeOrder,
        PaymentManager $paymentManager,
        ReleaseOrderStock $releaseOrderStock,
    ): SymfonyResponse {
        $checkoutData = $request->toCheckoutData();
        $order = $placeOrder->execute($request, $checkoutData);

        $driver = $order->paymentOption->driver ?? 'stripe';
        $gateway = $paymentManager->gateway($driver);
        $result = $gateway->createCheckout($order);

        if (! $result->successful) {
            $releaseOrderStock->execute($order);

            return back()->withErrors([
                'payment' => $result->errorMessage ?? 'Unable to process checkout. Please try again.',
            ]);
        }

        $this->cartService->clearCart($request);

        if ($driver === 'stripe' && $result->redirectUrl) {
            if ($result->sessionId) {
                $order->update(['stripe_session_id' => $result->sessionId]);

                Payment::updateOrCreate(
                    ['order_id' => $order->id, 'driver' => 'stripe'],
                    [
                        'stripe_session_id' => $result->sessionId,
                        'status' => PaymentStatus::Unpaid,
                        'amount' => $order->total,
                        'currency' => $order->currency,
                    ]
                );
            }

            return Inertia::location($result->redirectUrl);
        }

        // Non-stripe payment options (direct bank transfer, COD)
        Mail::to($order->customer_email)->queue(
            new OrderConfirmationMail($order)
        );

        return to_route('checkout.success', ['order' => $order->public_id]);
    }
}
