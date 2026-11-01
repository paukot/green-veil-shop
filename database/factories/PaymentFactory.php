<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'driver' => 'stripe',
            'stripe_session_id' => 'cs_test_'.$this->faker->regexify('[A-Za-z0-9]{24}'),
            'stripe_payment_intent_id' => 'pi_test_'.$this->faker->regexify('[A-Za-z0-9]{24}'),
            'status' => PaymentStatus::Unpaid,
            'amount' => 54.99,
            'currency' => 'EUR',
            'failure_reason' => null,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function failed(?string $reason = 'Payment declined'): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Failed,
            'failure_reason' => $reason,
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn () => [
            'order_id' => $order->id,
            'amount' => $order->total,
            'currency' => $order->currency,
        ]);
    }
}
