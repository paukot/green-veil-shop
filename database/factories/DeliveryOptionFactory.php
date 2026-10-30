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
}
