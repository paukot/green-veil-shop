<?php

namespace Database\Factories;

use App\Models\SliderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SliderItem>
 */
class SliderItemFactory extends Factory
{
    protected $model = SliderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => 'sliders/'.fake()->uuid().'.jpg',
            'url' => '/products',
            'url_opens_in_new_tab' => false,
            'is_active' => true,
            'sort_order' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'title' => fake()->words(3, true),
            'subtitle' => fake()->sentence(),
            'alt_text' => fake()->words(3, true),
            'button_label' => 'Shop Now',
        ];
    }

    public function visible(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }
}
