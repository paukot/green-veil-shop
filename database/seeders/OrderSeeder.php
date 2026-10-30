<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $deliveryOption = DeliveryOption::query()->where('code', 'standard')->firstOrFail();
        $promotion = Promotion::query()->where('code', 'WELCOME10')->firstOrFail();

        Order::updateOrCreate(
            ['number' => 'GV-DEMO-0001'],
            [
                'user_id' => $user->id,
                'delivery_option_id' => $deliveryOption->id,
                'promotion_id' => $promotion->id,
                'status' => OrderStatus::Processing,
                'payment_status' => PaymentStatus::Paid,
                'subtotal' => 41.40,
                'delivery_amount' => 4.99,
                'discount_amount' => 4.14,
                'tax_amount' => 0,
                'total' => 42.25,
                'currency' => 'EUR',
                'customer_name' => 'Admin',
                'customer_email' => 'admin@example.com',
                'customer_phone' => '+48123456789',
                'shipping_address' => ['line1' => 'Green Veil Studio', 'city' => 'Warsaw', 'postal_code' => '00-001', 'country' => 'PL'],
                'billing_address' => ['line1' => 'Green Veil Studio', 'city' => 'Warsaw', 'postal_code' => '00-001', 'country' => 'PL'],
                'notes' => 'Demo order for the development environment.',
            ],
        );
    }
}
