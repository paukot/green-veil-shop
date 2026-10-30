<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'sku' => fake()->unique()->bothify('GV-####-??'),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'price' => fake()->randomFloat(2, 10, 80),
            'stock_quantity' => fake()->numberBetween(5, 50),
            'track_inventory' => true,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
        ];
    }
}
