<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $price = fake()->randomFloat(2, 5, 80);

        return [
            'product_id' => Product::factory(),
            'name' => fake()->colorName(),
            'variant_type' => 'color',
            'color_hex' => fake()->hexColor(),
            'sku' => fake()->unique()->bothify('VAR-####-??'),
            'barcode' => fake()->ean13(),
            'price' => $price,
            'compare_at_price' => fake()->boolean(40) ? round($price * 1.25, 2) : null,
            'stock_quantity' => fake()->numberBetween(0, 50),
            'track_inventory' => true,
            'is_active' => true,
            'is_default' => false,
            'image' => 'product-variants/'.fake()->uuid().'.jpg',
            'sort_order' => 0,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function size(string $volume = '50ml'): static
    {
        return $this->state(fn () => [
            'name' => $volume,
            'variant_type' => 'size',
            'color_hex' => null,
        ]);
    }

    public function scent(string $scent = 'Rose & Honey'): static
    {
        return $this->state(fn () => [
            'name' => $scent,
            'variant_type' => 'scent',
            'color_hex' => null,
        ]);
    }
}
