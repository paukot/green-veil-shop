<?php

namespace App\Actions\Checkout;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ReleaseOrderStock
{
    /**
     * Release stock reserved for an uncompleted or cancelled order.
     */
    public function execute(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $product = Product::query()->where('id', $item->product_id)->lockForUpdate()->first();

                if ($product && $product->track_inventory) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }
        });
    }
}
