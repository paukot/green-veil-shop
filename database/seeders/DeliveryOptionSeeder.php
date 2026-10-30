<?php

namespace Database\Seeders;

use App\Models\DeliveryOption;
use Illuminate\Database\Seeder;

class DeliveryOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            [
                'code' => 'standard',
                'price' => 4.99,
                'free_threshold' => 50.00,
                'estimated_days_min' => 3,
                'estimated_days_max' => 5,
                'is_active' => true,
                'sort_order' => 0,
                'name' => 'Standard Delivery',
                'description' => 'Delivered in 3-5 business days.',
            ],
            [
                'code' => 'express',
                'price' => 9.99,
                'free_threshold' => 100.00,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'is_active' => true,
                'sort_order' => 1,
                'name' => 'Express Delivery',
                'description' => 'Delivered in 1-2 business days.',
            ],
            [
                'code' => 'pickup',
                'price' => 0.00,
                'free_threshold' => null,
                'estimated_days_min' => null,
                'estimated_days_max' => null,
                'is_active' => true,
                'sort_order' => 2,
                'name' => 'Click & Collect',
                'description' => 'Pick up your order in store.',
            ],
        ];

        foreach ($options as $option) {
            DeliveryOption::updateOrCreate(['code' => $option['code']], $option);
        }
    }
}
