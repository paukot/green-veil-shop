<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var string $name */
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
            'main_image' => 'products/main/'.fake()->uuid().'.jpg',
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => [
            'is_featured' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => [
            'stock_quantity' => 0,
            'track_inventory' => true,
        ]);
    }

    public function inStock(int $quantity = 10): static
    {
        return $this->state(fn () => [
            'stock_quantity' => $quantity,
            'track_inventory' => true,
        ]);
    }

    public function untrackedInventory(): static
    {
        return $this->state(fn () => [
            'track_inventory' => false,
        ]);
    }

    public function price(float $amount): static
    {
        return $this->state(fn () => [
            'price' => $amount,
        ]);
    }

    public function withoutCategory(): static
    {
        return $this->state(fn () => [
            'category_id' => null,
        ]);
    }

    public function withCategory(Category $category): static
    {
        return $this->state(fn () => [
            'category_id' => $category->id,
        ]);
    }

    public function withImages(int $count = 3): static
    {
        return $this->afterCreating(function (Product $product) use ($count) {
            for ($i = 0; $i < $count; $i++) {
                ProductImage::factory()->create([
                    'product_id' => $product->id,
                    'sort_order' => $i,
                    'is_primary' => $i === 0,
                ]);
            }
        });
    }

    /**
     * @param  array<int, Promotion>|Collection<int, Promotion>  $promotions
     */
    public function withPromotions(array|Collection $promotions): static
    {
        return $this->afterCreating(function (Product $product) use ($promotions) {
            $ids = $promotions instanceof Collection ? $promotions->pluck('id')->all() : collect($promotions)->pluck('id')->all();
            $product->promotions()->syncWithoutDetaching($ids);
        });
    }
}
