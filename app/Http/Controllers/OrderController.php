<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with(['items', 'deliveryOption', 'paymentOption'])
            ->latest('placed_at')
            ->paginate(10)
            ->through(fn (Order $order) => [
                'public_id' => $order->public_id,
                'number' => $order->number,
                'status' => $order->status->value,
                'payment_status' => $order->payment_status->value,
                'total' => (float) $order->total,
                'currency' => $order->currency,
                'placed_at' => $order->placed_at?->format('M j, Y') ?? 'Recently',
                'items_count' => (int) $order->items->sum('quantity'),
                'first_item_image' => $order->items->first()?->product_image,
                'delivery_name' => $order->deliveryOption?->name,
                'payment_driver' => $order->paymentOption?->driver,
            ]);

        return Inertia::render('Account/Orders', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        Gate::authorize('view', $order);

        $order->loadMissing(['items', 'deliveryOption', 'paymentOption', 'payments']);

        return Inertia::render('Account/Order', [
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
                'notes' => $order->notes,
                'placed_at' => $order->placed_at?->format('F j, Y, g:i a') ?? 'Recently',
                'paid_at' => $order->paid_at?->format('F j, Y, g:i a'),
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
}
