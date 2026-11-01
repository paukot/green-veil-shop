<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderItemResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentOptionResource;
use App\Http\Resources\SimpleDeliveryOptionResource;
use App\Http\Resources\SimpleOrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    const int DEFAULT_PAGINATION = 10;

    public function index(Request $request): Response
    {
        $user = $request->user();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with(['items', 'deliveryOption', 'paymentOption'])
            ->latest('placed_at')
            ->paginate(self::DEFAULT_PAGINATION);

        return Inertia::render('Account/Orders', [
            'orders' => SimpleOrderResource::collection($orders)->additional(['total' => $orders->total()]),
        ]);
    }

    public function show(Order $order): Response
    {
        Gate::authorize('view', $order);

        $order->loadMissing(['items', 'deliveryOption', 'paymentOption', 'payments']);

        return Inertia::render('Account/Order', [
            'order' => OrderResource::make($order),
            'items' => OrderItemResource::collection($order->items),
            'deliveryOption' => $order->deliveryOption ? SimpleDeliveryOptionResource::make($order->deliveryOption) : null,
            'paymentOption' => $order->paymentOption ? PaymentOptionResource::make($order->paymentOption) : null,
        ]);
    }
}
