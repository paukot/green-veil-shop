<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'number' => Order::generateNumber(),
            'user_id' => User::factory(),
            'delivery_option_id' => DeliveryOption::factory(),
            'payment_option_id' => null,
            'promotion_id' => null,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'stripe_session_id' => null,
            'stripe_payment_intent_id' => null,
            'stripe_charge_id' => null,
            'subtotal' => 50.00,
            'delivery_amount' => 4.99,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'total' => 54.99,
            'currency' => 'EUR',
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->phoneNumber(),
            'shipping_address' => [
                'line1' => '12 Rosewood Lane',
                'line2' => null,
                'city' => 'Dublin',
                'postal_code' => 'D02 X285',
                'country' => 'IE',
            ],
            'billing_address' => [
                'line1' => '12 Rosewood Lane',
                'line2' => null,
                'city' => 'Dublin',
                'postal_code' => 'D02 X285',
                'country' => 'IE',
            ],
            'notes' => null,
            'placed_at' => now(),
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
