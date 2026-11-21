<?php

namespace App\Actions\Checkout;

use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

class ReleaseOrderReservations
{
    /**
     * Return an order's reserved stock and promotion usage exactly once.
     *
     * Every path that abandons an order (gateway failure, failed or expired payment,
     * stale sweep, refund, admin cancellation) calls this, often more than one of them
     * for the same order, so the release is recorded on the order and repeated calls are no-ops.
     *
     * @return bool Whether this call released the reservations.
     */
    public function execute(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (! $lockedOrder || $lockedOrder->reservations_released_at !== null) {
                return false;
            }

            $this->restock($lockedOrder);

            if ($lockedOrder->promotion_id !== null) {
                Promotion::query()
                    ->whereKey($lockedOrder->promotion_id)
                    ->where('times_used', '>', 0)
                    ->decrement('times_used');
            }

            $lockedOrder->update(['reservations_released_at' => now()]);

            return true;
        });
    }

    private function restock(Order $order): void
    {
        /** @var array<int, int> $quantitiesByProduct */
        $quantitiesByProduct = $order->items()
            ->whereNotNull('product_id')
            ->get(['product_id', 'quantity'])
            ->groupBy('product_id')
            ->map(fn ($items): int => (int) $items->sum('quantity'))
            ->all();

        if ($quantitiesByProduct === []) {
            return;
        }

        $products = Product::query()
            ->whereKey(array_keys($quantitiesByProduct))
            ->where('track_inventory', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($products as $product) {
            $product->increment('stock_quantity', $quantitiesByProduct[$product->id]);
        }
    }
}
