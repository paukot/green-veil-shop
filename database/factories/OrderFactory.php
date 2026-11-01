<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentOption;
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
            'payment_option_id' => PaymentOption::factory(),
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

    public function guest(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
        ]);
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
        ]);
    }

    public function pendingPayment(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::PendingPayment,
            'payment_status' => PaymentStatus::Unpaid,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn () => [
            'payment_status' => PaymentStatus::Unpaid,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Cancelled,
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Shipped,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Delivered,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Failed,
        ]);
    }

    public function withoutPaymentOption(): static
    {
        return $this->state(fn () => [
            'payment_option_id' => null,
        ]);
    }

    public function withItems(int $count = 2, ?float $unitPrice = 25.00): static
    {
        return $this->afterCreating(function (Order $order) use ($count, $unitPrice) {
            $price = $unitPrice ?? 25.00;
            $items = OrderItem::factory()->count($count)->create([
                'order_id' => $order->id,
                'unit_price' => $price,
                'quantity' => 1,
                'subtotal' => $price,
            ]);

            $subtotal = round((float) $items->sum('subtotal'), 2);
            $total = round($subtotal + (float) $order->delivery_amount - (float) $order->discount_amount + (float) $order->tax_amount, 2);

            $order->updateQuietly([
                'subtotal' => $subtotal,
                'total' => $total,
            ]);
        });
    }
}
