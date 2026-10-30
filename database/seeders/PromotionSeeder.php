<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotion = Promotion::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'discount_type' => DiscountType::Percentage,
                'discount_value' => 10,
                'minimum_order_amount' => 25,
                'usage_limit' => 1000,
                'usage_limit_per_user' => 1,
                'times_used' => 0,
                'is_active' => true,
                'applies_to_all_products' => true,
                'name' => 'Welcome offer',
                'description' => 'Save 10% on your first order.',
            ],
        );

        $promotion->products()->sync(Product::query()->pluck('id'));
    }
}
