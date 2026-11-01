<?php

namespace Database\Factories;

use App\Models\PaymentOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentOption>
 */
class PaymentOptionFactory extends Factory
{
    protected $model = PaymentOption::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(1),
            'driver' => 'stripe',
            'name' => 'Credit / Debit Card',
            'description' => 'Pay securely via Stripe',
            'instructions' => null,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function stripe(): static
    {
        return $this->state(fn () => [
            'code' => 'stripe',
            'driver' => 'stripe',
            'name' => 'Credit or Debit Card',
            'description' => 'Pay securely online with cards or mobile wallets via Stripe.',
            'sort_order' => 1,
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn () => [
            'code' => 'bank_transfer',
            'driver' => 'bank_transfer',
            'name' => 'Direct Bank Transfer',
            'description' => 'Transfer directly into our European bank account.',
            'instructions' => "IBAN: IE89BOTANICAL0001234567\nBIC: BOTANICALIE2D\nPlease include your order number as reference.",
            'sort_order' => 2,
        ]);
    }

    public function cod(): static
    {
        return $this->state(fn () => [
            'code' => 'cod',
            'driver' => 'cod',
            'name' => 'Cash on Delivery',
            'description' => 'Pay with cash upon receipt of your package from our courier.',
            'instructions' => 'Please have exact cash ready for the courier driver.',
            'sort_order' => 3,
        ]);
    }
}
