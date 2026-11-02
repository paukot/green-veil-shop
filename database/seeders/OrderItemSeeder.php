<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderItemSeeder extends Seeder
{
    /**
     * Line items are generated directly in OrderSeeder to maintain accurate calculated totals,
     * promotions, delivery charges, and payment amounts.
     */
    public function run(): void
    {
        $ordersWithoutItems = Order::query()->whereDoesntHave('items')->get();
        if ($ordersWithoutItems->isEmpty()) {
            return;
        }

        $product = Product::query()->first();
        if (! $product) {
            return;
        }

        foreach ($ordersWithoutItems as $order) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'product_image' => $product->main_image,
                'unit_price' => $product->price,
                'quantity' => 1,
                'subtotal' => $product->price,
            ]);
        }
    }
}
