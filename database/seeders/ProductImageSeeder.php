<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            ['sku' => 'GV-SKIN-001', 'path' => 'products/botanical-cleansing-oil.jpg', 'sort_order' => 0, 'is_primary' => true],
            ['sku' => 'GV-SKIN-002', 'path' => 'products/rosewater-hydrating-mist.jpg', 'sort_order' => 0, 'is_primary' => true],
            ['sku' => 'GV-MAKE-001', 'path' => 'products/mineral-tinted-moisturizer.jpg', 'sort_order' => 0, 'is_primary' => true],
            ['sku' => 'GV-BODY-001', 'path' => 'products/lavender-body-butter.jpg', 'sort_order' => 0, 'is_primary' => true],
        ];

        foreach ($images as $image) {
            $product = Product::query()->where('sku', $image['sku'])->firstOrFail();

            ProductImage::updateOrCreate(
                ['product_id' => $product->id, 'path' => $image['path']],
                [
                    'alt_text' => $product->name,
                    'sort_order' => $image['sort_order'],
                    'is_primary' => $image['is_primary'],
                ],
            );
        }
    }
}
