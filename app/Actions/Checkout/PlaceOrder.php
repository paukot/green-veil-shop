<?php

namespace App\Actions\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public function __construct(
        protected CartService $cartService,
    ) {}

    /**
     * Create an order from the current cart with stock reservation and price snapshots.
     *
     * @throws ValidationException|\Throwable
     */
    public function execute(Request $request, CheckoutData $data): Order
    {
        return DB::transaction(function () use ($request, $data): Order {
            // Check raw cart to verify no inactive or non-existent products are being ordered
            $rawCart = $this->getRawCart($request);

            if (empty($rawCart)) {
                throw ValidationException::withMessages([
                    'cart' => ['Your shopping bag is empty. Please add items before placing an order.'],
                ]);
            }

            foreach ($rawCart as $key => $entry) {
                $rawId = is_array($entry) ? (int) ($entry['product_id'] ?? $entry['id'] ?? $key) : (int) $key;
                $productCheck = Product::find($rawId);

                if (! $productCheck || ! $productCheck->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => ['One or more products in your shopping bag are no longer available.'],
                    ]);
                }
            }

            /** @var Collection<int, object|array> $cartItems */
            $cartItems = $this->cartService->getItems($request);

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['Your shopping bag is empty. Please add items before placing an order.'],
                ]);
            }

            // Validate delivery option
            $deliveryOption = DeliveryOption::query()
                ->where('id', $data->deliveryOptionId)
                ->where('is_active', true)
                ->first();

            if (! $deliveryOption) {
                throw ValidationException::withMessages([
                    'delivery_option_id' => ['The selected delivery option is unavailable.'],
                ]);
            }

            // Validate payment option
            $paymentOption = PaymentOption::query()
                ->where('id', $data->paymentOptionId)
                ->where('is_active', true)
                ->first();

            if (! $paymentOption) {
                throw ValidationException::withMessages([
                    'payment_option_id' => ['The selected payment option is unavailable.'],
                ]);
            }

            $subtotal = 0.00;
            $orderItemsData = [];

            // Verify each product is active, lock for inventory check, and reserve stock
            foreach ($cartItems as $cartItem) {
                $productId = is_array($cartItem) ? (int) $cartItem['product_id'] : (int) $cartItem->product_id;
                $quantity = is_array($cartItem) ? (int) $cartItem['quantity'] : (int) $cartItem->quantity;

                /** @var Product|null $product */
                $product = Product::query()
                    ->where('id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => ['A product in your shopping bag is no longer available.'],
                    ]);
                }

                if ($product->track_inventory) {
                    if ($product->stock_quantity < $quantity) {
                        $available = max(0, $product->stock_quantity);
                        $msg = $available > 0
                            ? "Insufficient stock for '{$product->name}'. Only {$available} unit(s) remaining."
                            : "'{$product->name}' is now out of stock.";

                        throw ValidationException::withMessages([
                            'cart' => [$msg],
                        ]);
                    }

                    // Reserve stock immediately for order
                    $product->decrement('stock_quantity', $quantity);
                }

                $unitPrice = (float) $product->price;
                $lineSubtotal = round($unitPrice * $quantity, 2);
                $subtotal += $lineSubtotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'product_image' => $product->main_image,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $subtotal = round($subtotal, 2);

            // Compute promotions server-side
            $discountAmount = 0.00;
            $promotionId = null;

            $promotion = Promotion::query()
                ->active()
                ->where('applies_to_all_products', true)
                ->first();

            if ($promotion && $promotion->isCurrentlyActive()) {
                if ($promotion->minimum_order_amount === null || $subtotal >= (float) $promotion->minimum_order_amount) {
                    $discountAmount = round($promotion->applyDiscount($subtotal), 2);
                    $promotionId = $promotion->id;
                }
            }

            // Compute delivery amount
            $deliveryAmount = (float) $deliveryOption->price;
            if ($deliveryOption->free_threshold !== null && $subtotal >= (float) $deliveryOption->free_threshold) {
                $deliveryAmount = 0.00;
            }

            $total = max(0.00, round($subtotal - $discountAmount + $deliveryAmount, 2));

            // Create Order
            $order = Order::create([
                'user_id' => $data->user?->id,
                'delivery_option_id' => $deliveryOption->id,
                'payment_option_id' => $paymentOption->id,
                'promotion_id' => $promotionId,
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
                'subtotal' => $subtotal,
                'delivery_amount' => $deliveryAmount,
                'discount_amount' => $discountAmount,
                'tax_amount' => 0.00,
                'total' => $total,
                'currency' => 'EUR',
                'customer_name' => $data->customerName,
                'customer_email' => $data->customerEmail,
                'customer_phone' => $data->customerPhone,
                'shipping_address' => $data->shippingAddress,
                'billing_address' => $data->billingAddress,
                'notes' => $data->notes,
                'placed_at' => now(),
            ]);

            // Save Order Items snapshots
            foreach ($orderItemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            return $order;
        });
    }

    private function getRawCart(Request $request): array
    {
        if ($user = $request->user()) {
            return CartItem::where('user_id', $user->id)
                ->pluck('quantity', 'product_id')
                ->all();
        }

        if ($request->hasSession()) {
            return $request->session()->get('cart', []);
        }

        return [];
    }
}
