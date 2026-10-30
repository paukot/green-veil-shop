<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->pluck('id', 'slug');

        $products = [
            [
                'sku' => 'GV-SKIN-001',
                'slug' => 'botanical-cleansing-oil',
                'category_id' => $categories['skincare'],
                'price' => 24.90,
                'compare_at_price' => 29.90,
                'stock_quantity' => 42,
                'is_featured' => true,
                'sort_order' => 0,
                'main_image' => 'products/botanical-cleansing-oil.jpg',
                'name' => 'Botanical Cleansing Oil',
                'short_description' => 'A gentle oil cleanser with calendula and jojoba.',
                'description' => 'Melts away makeup and impurities while leaving skin soft and comfortable.',
            ],
            [
                'sku' => 'GV-SKIN-002',
                'slug' => 'rosewater-hydrating-mist',
                'category_id' => $categories['skincare'],
                'price' => 16.50,
                'compare_at_price' => null,
                'stock_quantity' => 68,
                'is_featured' => true,
                'sort_order' => 1,
                'main_image' => 'products/rosewater-hydrating-mist.jpg',
                'name' => 'Rosewater Hydrating Mist',
                'short_description' => 'A refreshing mist for an instant moisture boost.',
                'description' => 'Use throughout the day to refresh and comfort dehydrated skin.',
            ],
            [
                'sku' => 'GV-MAKE-001',
                'slug' => 'mineral-tinted-moisturizer',
                'category_id' => $categories['makeup'],
                'price' => 32.00,
                'compare_at_price' => 36.00,
                'stock_quantity' => 25,
                'is_featured' => true,
                'sort_order' => 2,
                'main_image' => 'products/mineral-tinted-moisturizer.jpg',
                'name' => 'Mineral Tinted Moisturizer',
                'short_description' => 'Light, breathable coverage with mineral protection.',
                'description' => 'A buildable tint that evens tone while keeping the complexion fresh.',
            ],
            [
                'sku' => 'GV-BODY-001',
                'slug' => 'lavender-body-butter',
                'category_id' => $categories['body-care'],
                'price' => 21.00,
                'compare_at_price' => null,
                'stock_quantity' => 31,
                'is_featured' => false,
                'sort_order' => 3,
                'main_image' => 'products/lavender-body-butter.jpg',
                'name' => 'Lavender Body Butter',
                'short_description' => 'Rich body butter with shea and lavender oil.',
                'description' => 'Deeply nourishes dry skin with a calming botanical scent.',
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['sku' => $product['sku']], $product);
        }
    }
}
