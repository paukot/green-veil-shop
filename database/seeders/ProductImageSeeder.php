<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    /**
     * Seed supplementary high-resolution product gallery images.
     */
    public function run(): void
    {
        $galleryImages = [
            'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1608248597359-0a6723c34898?auto=format&fit=crop&w=600&q=80',
        ];

        // Attach gallery photos to featured products if not already seeded
        $products = Product::query()->where('is_featured', true)->take(50)->get();

        foreach ($products as $index => $product) {
            $path = $galleryImages[$index % count($galleryImages)];

            ProductImage::firstOrCreate(
                ['product_id' => $product->id, 'path' => $path],
                [
                    'alt_text' => "{$product->name} detail shot",
                    'sort_order' => 2,
                    'is_primary' => false,
                ],
            );
        }
    }
}
