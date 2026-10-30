<?php

namespace Database\Seeders;

use App\Models\PaymentOption;
use Illuminate\Database\Seeder;

class PaymentOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            [
                'code' => 'stripe',
                'driver' => 'stripe',
                'name' => 'Credit / Debit Card (Stripe)',
                'description' => 'Pay securely online with Visa, Mastercard, Apple Pay, or Google Pay.',
                'instructions' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'bank_transfer',
                'driver' => 'bank_transfer',
                'name' => 'Direct Bank Transfer',
                'description' => 'Transfer directly into our botanical apothecary account. Your order will ship once funds clear.',
                'instructions' => "Bank: Botanical Apothecary Bank Ireland\nIBAN: IE89BOTANICAL0001234567\nBIC: BOTANICALIE2D\nPlease use your Order Number as payment reference.",
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'cod',
                'driver' => 'cod',
                'name' => 'Cash on Delivery',
                'description' => 'Pay cash to the courier upon delivery of your botanical parcels.',
                'instructions' => 'Please have the exact total prepared for the courier.',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($options as $option) {
            PaymentOption::updateOrCreate(
                ['code' => $option['code']],
                $option
            );
        }
    }
}
