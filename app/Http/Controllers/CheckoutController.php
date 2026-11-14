<?php

namespace App\Http\Controllers;

use App\Actions\Checkout\PlaceOrder;
use App\Actions\Checkout\RecordPaymentSession;
use App\Actions\Checkout\ReleaseOrderStock;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CustomerAddressResource;
use App\Http\Resources\PaymentOptionResource;
use App\Mail\OrderConfirmationMail;
use App\Models\Country;
use App\Models\PaymentOption;
use App\Models\User;
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
        $overview = $this->cartService->getCartOverview($request);

        if ($overview['items']->isEmpty()) {
            return redirect()->route('cart.index')->with('info', 'Your shopping bag is empty.');
        }

        $paymentOptions = PaymentOption::getActiveOptions();

        $user = $request->user();

        return Inertia::render('Checkout/Index', [
            'items' => CartItemResource::collection($overview['items']),
            'summary' => $overview['summary'],
            'appliedPromotion' => $overview['applied_promotion'],
            'availablePromotions' => $overview['available_promotions'],
            'deliveryOptions' => $overview['delivery_options'],
            'paymentOptions' => PaymentOptionResource::collection($paymentOptions),
            'countries' => Country::getShippingOptions(),
            'customer' => $user instanceof User ? [
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'savedAddresses' => $user instanceof User
                ? CustomerAddressResource::collection($user->addresses)->resolve()
                : [],
            'canSaveAddress' => $user instanceof User ? $user->canAddAddress() : false,
        ]);
    }

    public function store(
        CheckoutRequest $request,
        PlaceOrder $placeOrder,
        PaymentManager $paymentManager,
        ReleaseOrderStock $releaseOrderStock,
        RecordPaymentSession $recordPaymentSession,
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
            $recordPaymentSession->execute($order, 'stripe', $result->sessionId);

            return Inertia::location($result->redirectUrl);
        }

        // Non-stripe payment options (direct bank transfer, COD)
        Mail::to($order->customer_email)->queue(
            new OrderConfirmationMail($order)
        );

        return to_route('checkout.success', ['order' => $order->public_id]);
    }
}
