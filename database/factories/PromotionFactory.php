<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Botanical Seasonal Welcome',
            'code' => 'BOTANICAL'.$this->faker->unique()->randomNumber(3),
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 10.00,
            'minimum_order_amount' => null,
            'usage_limit' => null,
            'usage_limit_per_user' => null,
            'times_used' => 0,
            'is_active' => true,
            'applies_to_all_products' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'description' => 'A special seasonal discount on all pure botanical formulations.',
        ];
    }
}
