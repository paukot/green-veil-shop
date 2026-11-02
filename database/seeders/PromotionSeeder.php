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
        $promotions = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome Offer',
                'description' => 'Save 10% on your first clean beauty order.',
                'discount_type' => DiscountType::Percentage,
                'discount_value' => 10,
                'minimum_order_amount' => 25,
                'usage_limit' => 5000,
                'usage_limit_per_user' => 1,
                'times_used' => 142,
                'is_active' => true,
                'applies_to_all_products' => true,
            ],
            [
                'code' => 'GLOW20',
                'name' => 'Botanical Glow Discount',
                'description' => '20% off orders over €60 for radiant skin.',
                'discount_type' => DiscountType::Percentage,
                'discount_value' => 20,
                'minimum_order_amount' => 60,
                'usage_limit' => 1000,
                'usage_limit_per_user' => 2,
                'times_used' => 89,
                'is_active' => true,
                'applies_to_all_products' => true,
            ],
            [
                'code' => 'SUMMER15',
                'name' => 'Summer Glow Weekend',
                'description' => '15% off across all catalog items.',
                'discount_type' => DiscountType::Percentage,
                'discount_value' => 15,
                'minimum_order_amount' => 40,
                'usage_limit' => 2500,
                'usage_limit_per_user' => 1,
                'times_used' => 312,
                'is_active' => true,
                'applies_to_all_products' => true,
            ],
            [
                'code' => 'FIXED5',
                'name' => 'Direct €5 Voucher',
                'description' => 'Save €5 on any order over €30.',
                'discount_type' => DiscountType::FixedAmount,
                'discount_value' => 5,
                'minimum_order_amount' => 30,
                'usage_limit' => 10000,
                'usage_limit_per_user' => 1,
                'times_used' => 420,
                'is_active' => true,
                'applies_to_all_products' => true,
            ],
            [
                'code' => 'BEAUTYVIP',
                'name' => 'VIP Member Privilege',
                'description' => '25% off luxury fragrances and featured skincare.',
                'discount_type' => DiscountType::Percentage,
                'discount_value' => 25,
                'minimum_order_amount' => 80,
                'usage_limit' => 500,
                'usage_limit_per_user' => 3,
                'times_used' => 54,
                'is_active' => true,
                'applies_to_all_products' => false,
            ],
        ];

        $featuredProductIds = Product::query()->where('is_featured', true)->take(30)->pluck('id');

        foreach ($promotions as $promoData) {
            $promo = Promotion::updateOrCreate(['code' => $promoData['code']], $promoData);

            if (! $promo->applies_to_all_products && $featuredProductIds->isNotEmpty()) {
                $promo->products()->sync($featuredProductIds);
            }
        }
    }
}
