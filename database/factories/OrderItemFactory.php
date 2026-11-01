<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => $this->faker->words(3, true),
            'product_sku' => 'GV-'.strtoupper($this->faker->bothify('??-####')),
            'product_image' => 'products/main/'.fake()->uuid().'.jpg',
            'unit_price' => 25.00,
            'quantity' => 2,
            'subtotal' => 50.00,
        ];
    }

    public function forProduct(Product $product, int $quantity = 1): static
    {
        $unitPrice = (float) $product->price;
        $subtotal = round($unitPrice * $quantity, 2);

        return $this->state(fn () => [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku ?? 'GV-'.strtoupper(fake()->bothify('??-####')),
            'product_image' => $product->main_image,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
        ]);
    }

    public function unitPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $price,
            'subtotal' => round($price * ($attributes['quantity'] ?? 1), 2),
        ]);
    }

    public function quantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'subtotal' => round(($attributes['unit_price'] ?? 25.00) * $quantity, 2),
        ]);
    }
}
