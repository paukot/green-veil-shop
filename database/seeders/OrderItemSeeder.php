<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        $order = Order::query()->where('number', 'GV-DEMO-0001')->firstOrFail();

        foreach ([['sku' => 'GV-SKIN-001', 'quantity' => 1], ['sku' => 'GV-SKIN-002', 'quantity' => 1]] as $item) {
            $product = Product::query()->where('sku', $item['sku'])->firstOrFail();

            OrderItem::updateOrCreate(
                ['order_id' => $order->id, 'product_id' => $product->id],
                [
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'product_image' => $product->main_image,
                    'unit_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $product->price * $item['quantity'],
                ],
            );
        }
    }
}
