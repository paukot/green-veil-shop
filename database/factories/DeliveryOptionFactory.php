<?php

namespace Database\Factories;

use App\Models\DeliveryOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOption>
 */
class DeliveryOptionFactory extends Factory
{
    protected $model = DeliveryOption::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(1),
            'name' => 'Standard Botanical Delivery',
            'price' => 4.99,
            'free_threshold' => 50.00,
            'estimated_days_min' => 2,
            'estimated_days_max' => 4,
            'is_active' => true,
            'sort_order' => 1,
            'description' => 'Dispatched in recyclable botanical packaging.',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'price' => 0.00,
            'free_threshold' => 0.00,
        ]);
    }

    public function withThreshold(float $threshold): static
    {
        return $this->state(fn () => [
            'free_threshold' => $threshold,
        ]);
    }

    public function withoutThreshold(): static
    {
        return $this->state(fn () => [
            'free_threshold' => null,
        ]);
    }

    public function standard(): static
    {
        return $this->state(fn () => [
            'code' => 'standard',
            'name' => 'Standard Botanical Delivery',
            'price' => 4.99,
            'free_threshold' => 50.00,
            'sort_order' => 1,
        ]);
    }

    public function express(): static
    {
        return $this->state(fn () => [
            'code' => 'express',
            'name' => 'Express Botanical Courier',
            'price' => 9.99,
            'free_threshold' => null,
            'sort_order' => 2,
        ]);
    }
}
