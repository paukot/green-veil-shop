<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
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
        $summary = $this->cartService->getSummary($request);

        $standardDelivery = DeliveryOption::query()
            ->active()
            ->whereNotNull('free_threshold')
            ->orderBy('free_threshold')
            ->first();

        $freeShippingThreshold = $standardDelivery?->free_threshold ? (float) $standardDelivery->free_threshold : 50.00;
        $amountUntilFreeShipping = max(0, round($freeShippingThreshold - $summary['subtotal'], 2));

        $formattedItems = $cartItems->map(fn ($item) => [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'subtotal' => $item instanceof CartItem ? $item->subtotal() : (float) $item->subtotal,
            'product' => [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
                'sku' => $item->product->sku,
                'price' => (float) $item->product->price,
                'compare_at_price' => $item->product->compare_at_price ? (float) $item->product->compare_at_price : null,
                'discount_percentage' => $item->product->discountPercentage(),
                'main_image' => $item->product->main_image,
                'stock_quantity' => (int) $item->product->stock_quantity,
                'track_inventory' => (bool) $item->product->track_inventory,
                'is_in_stock' => $item->product->isInStock(),
                'category' => $item->product->category ? [
                    'id' => $item->product->category->id,
                    'name' => $item->product->category->name,
                    'slug' => $item->product->category->slug,
                ] : null,
            ],
        ])->values();

        $deliveryOptions = DeliveryOption::query()
            ->active()
            ->get(['id', 'code', 'name', 'price', 'free_threshold', 'estimated_days_min', 'estimated_days_max', 'description'])
            ->map(fn (DeliveryOption $option) => [
                'id' => $option->id,
                'code' => $option->code,
                'name' => $option->name,
                'price' => (float) $option->price,
                'free_threshold' => $option->free_threshold ? (float) $option->free_threshold : null,
                'is_free' => $option->free_threshold !== null && $summary['subtotal'] >= (float) $option->free_threshold,
                'delivery_label' => $option->estimatedDeliveryLabel(),
                'description' => $option->description,
            ]);

        return Inertia::render('Cart/Index', [
            'items' => $formattedItems,
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
