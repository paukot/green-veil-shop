<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartItemResource;
use App\Http\Resources\DeliveryOptionResource;
use App\Models\DeliveryOption;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(Request $request): Response
    {
        $cartItems = $this->cartService->getItems($request);
        $summary = $this->cartService->getSummaryFromCartItems($cartItems);

        $standardDelivery = DeliveryOption::query()
            ->active()
            ->whereNotNull('free_threshold')
            ->orderBy('free_threshold')
            ->first();

        $freeShippingThreshold = $standardDelivery?->free_threshold ? (float) $standardDelivery->free_threshold : 50.00;
        $amountUntilFreeShipping = max(0, round($freeShippingThreshold - $summary['subtotal'], 2));

        $deliveryOptions = DeliveryOption::query()
            ->active()
            ->get()
            ->map(fn ($deliveryOption) => DeliveryOptionResource::make($deliveryOption)
                ->withSubtotal($summary['subtotal'])
            );

        return Inertia::render('Cart/Index', [
            'items' => CartItemResource::collection($cartItems)->resolve(),
            'summary' => [
                'count' => $summary['count'],
                'subtotal' => $summary['subtotal'],
                'free_shipping_threshold' => $freeShippingThreshold,
                'amount_until_free_shipping' => (float) $amountUntilFreeShipping,
                'qualifies_for_free_shipping' => $summary['subtotal'] >= $freeShippingThreshold,
            ],
            'deliveryOptions' => $deliveryOptions,
        ]);
    }
}
