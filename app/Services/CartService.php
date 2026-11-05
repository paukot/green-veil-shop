<?php

namespace App\Services;

use App\Http\Resources\CartItemResource;
use App\Http\Resources\DeliveryOptionResource;
use App\Models\CartItem;
use App\Models\DeliveryOption;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Get all active cart items for the current user (database) or guest (session).
     *
     * @return Collection<int, CartItem>
     */
    public function getItems(Request $request): Collection
    {
        $user = $request->user();
        if ($user instanceof User) {
            return $this->getItemsFromUser($user);
        }

        return $this->getItemsFromSession($request);
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getItemsFromSession(Request $request): Collection
    {
        $sessionCart = $request->session()->get('cart', []);
        if (! is_array($sessionCart) || empty($sessionCart)) {
            return collect();
        }

        $productIds = collect($sessionCart)
            ->map(fn ($item, $key) => is_array($item) ? ($item['product_id'] ?? $key) : $key)
            ->filter();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->with('category:id,slug,name')
            ->get()
            ->keyBy('id');

        return collect($sessionCart)
            ->map(function ($item, $key) use ($products) {
                $productId = (int) (is_array($item) ? ($item['product_id'] ?? $key) : $key);
                $quantity = (int) (is_array($item) ? ($item['quantity'] ?? 1) : $item);
                $id = is_array($item) ? ($item['id'] ?? $productId) : $productId;

                $model = (new CartItem)->forceFill([
                    'id' => $id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);

                return $model->setRelation('product', $products->get($productId));
            })
            ->filter(fn (CartItem $item): bool => $item->product !== null);
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getItemsFromUser(User $user): Collection
    {
        return CartItem::query()
            ->where('user_id', $user->id)
            ->with(['product' => fn ($query) => $query->where('is_active', true)->with('category:id,slug,name')])
            ->get();
    }

    /**
     * @param  Collection<int, CartItem>  $cartItems
     * @return array{count: int, subtotal: float}
     */
    public function getSummaryFromCartItems(Collection $cartItems): array
    {
        if ($cartItems->isEmpty()) {
            return [
                'count' => 0,
                'subtotal' => 0.0,
            ];
        }

        return [
            'count' => (int) $cartItems->sum('quantity'),
            'subtotal' => round((float) $cartItems->sum(fn (CartItem $item) => $item->subtotal()), 2),
        ];
    }

    /**
     * Get count of items and subtotal for badges and summary.
     *
     * @return array{count: int, subtotal: float}
     */
    public function getSummary(Request $request): array
    {
        $items = $this->getItems($request);

        if ($items->isNotEmpty()) {
            return $this->getSummaryFromCartItems($items);
        }

        // Fallback for session structures where products aren't yet populated or in tests
        $sessionCart = $request->session()->get('cart', []);
        if (is_array($sessionCart) && ! empty($sessionCart)) {
            $count = (int) array_sum(array_column($sessionCart, 'quantity'));
            $subtotal = (float) array_sum(array_map(
                fn ($item) => ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 0)),
                $sessionCart
            ));

            return [
                'count' => $count,
                'subtotal' => round($subtotal, 2),
            ];
        }

        return [
            'count' => 0,
            'subtotal' => 0.0,
        ];
    }

    /**
     * Add a product to the cart with stock and active status validation.
     *
     * @throws ValidationException
     */
    public function addItem(Request $request, int $productId, int $quantity = 1): void
    {
        $product = Product::query()
            ->where('id', $productId)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            throw ValidationException::withMessages([
                'product_id' => ['The selected product is unavailable or inactive.'],
            ]);
        }

        if ($product->track_inventory && $product->stock_quantity < 1) {
            throw ValidationException::withMessages([
                'product_id' => ['This product is currently out of stock.'],
            ]);
        }

        if ($user = $request->user()) {
            $existing = CartItem::query()
                ->where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            $target = $existing ? ($existing->quantity + $quantity) : $quantity;

            if ($product->track_inventory && $target > $product->stock_quantity) {
                $available = $product->stock_quantity - ($existing ? $existing->quantity : 0);
                $msg = $available > 0
                    ? "Only {$available} more units available in stock."
                    : "You already have all {$product->stock_quantity} available units in your bag.";

                throw ValidationException::withMessages([
                    'quantity' => [$msg],
                ]);
            }

            if ($existing) {
                $existing->update(['quantity' => $target]);
            } else {
                CartItem::create([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'quantity' => $target,
                ]);
            }

            return;
        }

        // Guest session cart
        $sessionCart = $request->session()->get('cart', []);
        $currentQty = 0;

        if (isset($sessionCart[$productId])) {
            $currentQty = (int) ($sessionCart[$productId]['quantity'] ?? $sessionCart[$productId]);
        }

        $target = $currentQty + $quantity;

        if ($product->track_inventory && $target > $product->stock_quantity) {
            $available = $product->stock_quantity - $currentQty;
            $msg = $available > 0
                ? "Only {$available} more units available in stock."
                : "You already have all {$product->stock_quantity} available units in your bag.";

            throw ValidationException::withMessages([
                'quantity' => [$msg],
            ]);
        }

        $sessionCart[$productId] = [
            'id' => $productId,
            'product_id' => $productId,
            'quantity' => $target,
        ];

        $request->session()->put('cart', $sessionCart);
    }

    /**
     * Update quantity of an existing cart item with stock validation.
     *
     * @throws ValidationException
     */
    public function updateItem(Request $request, string|int $itemKey, int $quantity): void
    {
        if ($user = $request->user()) {
            $cartItem = CartItem::query()
                ->where('user_id', $user->id)
                ->where(fn ($query) => $query->where('id', $itemKey)->orWhere('product_id', $itemKey))
                ->first();

            if (! $cartItem) {
                if (CartItem::query()->where('id', $itemKey)->exists()) {
                    abort(403, 'Unauthorized cart action.');
                }
                abort(404, 'Cart item not found.');
            }

            $product = $cartItem->product;
            if (! $product || ! $product->is_active) {
                $cartItem->delete();
                throw ValidationException::withMessages([
                    'cart' => ['This product is no longer available and was removed from your bag.'],
                ]);
            }

            if ($product->track_inventory && $quantity > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ["Only {$product->stock_quantity} units available in stock."],
                ]);
            }

            $cartItem->update(['quantity' => $quantity]);

            return;
        }

        // Guest session
        $sessionCart = $request->session()->get('cart', []);
        $productId = (int) $itemKey;

        if (! isset($sessionCart[$productId])) {
            abort(404, 'Cart item not found.');
        }

        $product = Product::query()->where('id', $productId)->where('is_active', true)->first();
        if (! $product) {
            unset($sessionCart[$productId]);
            $request->session()->put('cart', $sessionCart);
            throw ValidationException::withMessages([
                'cart' => ['This product is no longer available.'],
            ]);
        }

        if ($product->track_inventory && $quantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => ["Only {$product->stock_quantity} units available in stock."],
            ]);
        }

        $sessionCart[$productId] = [
            'id' => $productId,
            'product_id' => $productId,
            'quantity' => $quantity,
        ];
        $request->session()->put('cart', $sessionCart);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(Request $request, string|int $itemKey): void
    {
        if ($user = $request->user()) {
            $cartItem = CartItem::query()
                ->where('user_id', $user->id)
                ->where(fn ($query) => $query->where('id', $itemKey)->orWhere('product_id', $itemKey))
                ->first();

            if (! $cartItem) {
                if (CartItem::query()->where('id', $itemKey)->exists()) {
                    abort(403, 'Unauthorized cart action.');
                }

                return;
            }

            $cartItem->delete();

            return;
        }

        // Guest session
        $sessionCart = $request->session()->get('cart', []);
        $productId = (int) $itemKey;

        if (isset($sessionCart[$productId])) {
            unset($sessionCart[$productId]);
            $request->session()->put('cart', $sessionCart);
        }
    }

    /**
     * Clear all items from the current cart.
     */
    public function clearCart(Request $request): void
    {
        if ($user = $request->user()) {
            CartItem::query()->where('user_id', $user->id)->delete();

            return;
        }

        $request->session()->forget('cart');
    }

    /**
     * Merge guest session cart items into authenticated user records on login/register.
     *
     * @param  Request|array<array-key, mixed>  $requestOrCart
     */
    public function mergeGuestCart(Request|array $requestOrCart, User $user): void
    {
        $sessionCart = $requestOrCart;
        if ($requestOrCart instanceof Request) {
            $sessionCart = $requestOrCart->session()->get('cart', []);
        }

        if (! is_array($sessionCart) || empty($sessionCart)) {
            return;
        }

        foreach ($sessionCart as $key => $entry) {
            $productId = is_array($entry) ? (int) ($entry['product_id'] ?? $entry['id'] ?? $key) : (int) $key;
            $quantity = is_array($entry) ? (int) ($entry['quantity'] ?? 1) : (int) $entry;

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $product = Product::query()->where('id', $productId)->where('is_active', true)->first();
            if (! $product) {
                continue;
            }

            $existing = CartItem::query()
                ->where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            $target = $existing ? ($existing->quantity + $quantity) : $quantity;

            if ($product->track_inventory && $target > $product->stock_quantity) {
                $target = $product->stock_quantity;
            }

            if ($existing) {
                $existing->update(['quantity' => $target]);
            } else {
                CartItem::create([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'quantity' => $target,
                ]);
            }
        }

        if ($requestOrCart instanceof Request && $requestOrCart->hasSession()) {
            $requestOrCart->session()->forget('cart');
        }
    }

    /**
     * Atomically sync cart items from frontend local storage.
     * Reconciles inventory limits, active status, and recalculates summary and delivery options.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @return array<string, mixed>
     */
    public function syncCart(Request $request, array $items): array
    {
        $warnings = [];
        $productIds = collect($items)->pluck('product_id')->filter()->unique();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with(['category:id,slug,name', 'brand:id,slug,name'])
            ->get()
            ->keyBy('id');

        $reconciledItems = [];

        foreach ($items as $entry) {
            $productId = (int) $entry['product_id'];
            $requestedQty = (int) $entry['quantity'];

            if ($productId <= 0 || $requestedQty <= 0) {
                continue;
            }

            $product = $products->get($productId);

            // Inactive or deleted product
            if (! $product || ! $product->is_active) {
                $name = $product ? $product->name : "Item #{$productId}";
                $warnings[] = "{$name} is no longer available and was removed from your bag.";

                continue;
            }

            // Check stock
            $finalQty = $requestedQty;
            if ($product->track_inventory) {
                if ($product->stock_quantity <= 0) {
                    $warnings[] = "{$product->name} is currently out of stock and was removed from your bag.";

                    continue;
                }

                if ($requestedQty > $product->stock_quantity) {
                    $finalQty = $product->stock_quantity;
                    $warnings[] = "Limited stock: {$product->name} quantity was adjusted to {$finalQty} available.";
                }
            }

            $reconciledItems[$productId] = $finalQty;
        }

        // Persist reconciled state
        $user = $request->user();
        if ($user instanceof User) {
            // Delete items not in reconciled set
            CartItem::query()
                ->where('user_id', $user->id)
                ->whereNotIn('product_id', array_keys($reconciledItems))
                ->delete();

            // Upsert remaining
            foreach ($reconciledItems as $pId => $qty) {
                CartItem::updateOrCreate(
                    ['user_id' => $user->id, 'product_id' => $pId],
                    ['quantity' => $qty]
                );
            }

            $currentCartItems = $this->getItemsFromUser($user);
        } else {
            // Guest session
            $sessionCart = [];
            foreach ($reconciledItems as $pId => $qty) {
                $sessionCart[$pId] = [
                    'id' => $pId,
                    'product_id' => $pId,
                    'quantity' => $qty,
                ];
            }
            $request->session()->put('cart', $sessionCart);

            $currentCartItems = $this->getItemsFromSession($request);
        }

        $summary = $this->getSummaryFromCartItems($currentCartItems);

        // Fetch delivery options and calculate thresholds
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

        return [
            'items' => CartItemResource::collection($currentCartItems)->resolve(),
            'summary' => [
                'count' => $summary['count'],
                'subtotal' => $summary['subtotal'],
                'free_shipping_threshold' => $freeShippingThreshold,
                'amount_until_free_shipping' => (float) $amountUntilFreeShipping,
                'qualifies_for_free_shipping' => $summary['subtotal'] >= $freeShippingThreshold,
            ],
            'delivery_options' => $deliveryOptions,
            'warnings' => $warnings,
        ];
    }
}
