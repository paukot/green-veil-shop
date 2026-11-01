<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var string $name */
        $name = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'image' => 'categories/'.fake()->uuid().'.jpg',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function asChildOf(Category $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
        ]);
    }

    public function withChildren(int $count = 2): static
    {
        return $this->afterCreating(function (Category $category) use ($count) {
            Category::factory()->count($count)->create([
                'parent_id' => $category->id,
            ]);
        });
    }

    public function withProducts(int $count = 3): static
    {
        return $this->afterCreating(function (Category $category) use ($count) {
            Product::factory()->count($count)->create([
                'category_id' => $category->id,
            ]);
        });
    }
}
