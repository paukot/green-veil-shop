<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
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
            'brand_id' => null,
            'series' => fake()->boolean(60) ? fake()->words(2, true) : null,
            'sku' => fake()->unique()->bothify('GV-####-??'),
            'barcode' => fake()->ean13(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'price' => fake()->randomFloat(2, 10, 80),
            'compare_at_price' => fake()->boolean(40) ? fake()->randomFloat(2, 85, 120) : null,
            'volume' => fake()->randomElement([7.2, 10.0, 30.0, 50.0, 100.0, 200.0, 250.0]),
            'unit' => 'ml',
            'unit_price_base_amount' => 100,
            'classification' => fake()->randomElement(['drugstore', 'dermocosmetic', 'natural_organic', 'luxury', 'professional']),
            'gender' => fake()->randomElement(['unisex', 'women', 'men']),
            'age_range' => fake()->randomElement(['all', '20+', '30+', '40+']),
            'formula_type' => fake()->randomElement(['cream', 'serum', 'gel', 'liquid', 'oil', 'balm']),
            'spf' => fake()->randomElement(['none', 'SPF 15', 'SPF 30', 'SPF 50']),
            'custom_characteristics' => [
                'Texture' => 'Lightweight',
                'Finish' => 'Natural Glow',
            ],
            'inci_list' => 'Aqua/Water, Glycerin, Dimethicone, Niacinamide, Cetearyl Alcohol, Sodium Hyaluronate, Tocopherol, Phenoxyethanol.',
            'how_to_use' => 'Apply gently onto cleansed face and neck morning and evening. Avoid direct eye contact.',
            'warnings' => 'For external use only. Discontinue use if irritation occurs. Store in a cool, dry place below 25°C.',
            'manufactured_in' => fake()->randomElement(['PL', 'FR', 'IT', 'DE', 'US', 'KR']),
            'pao_months' => fake()->randomElement([6, 12, 24]),
            'responsible_person' => 'Green Veil Cosmetics Sp. z o.o., Warsaw, Poland',
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

    public function withBrand(?Brand $brand = null): static
    {
        return $this->state(fn () => [
            'brand_id' => $brand ? $brand->id : Brand::factory(),
        ]);
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

    public function withVariants(int $count = 3): static
    {
        return $this->afterCreating(function (Product $product) use ($count) {
            for ($i = 0; $i < $count; $i++) {
                ProductVariant::factory()->create([
                    'product_id' => $product->id,
                    'is_default' => $i === 0,
                    'sort_order' => $i,
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
