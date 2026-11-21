<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Database\Seeders\Support\SeedImages;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    /**
     * Give the first catalogue products a two-photo gallery when they have fewer than two images.
     */
    public function run(): void
    {
        $seedImages = SeedImages::make();

        $products = Product::query()
            ->with(['category.parent', 'images'])
            ->take(300)
            ->get();

        foreach ($products as $product) {
            if ($product->images->count() >= 2 || $product->category === null) {
                continue;
            }

            $domain = ProductSeeder::resolveDomainKey($product->category);

            $gallery = [
                2 => [$seedImages->productImage($domain, $product->sku, 1), 'formula & texture detail'],
                3 => [$seedImages->productImage($domain, $product->sku, 2), 'botanical packaging detail'],
            ];

            foreach ($gallery as $sortOrder => [$path, $caption]) {
                if ($path === null) {
                    continue;
                }

                ProductImage::firstOrCreate(
                    ['product_id' => $product->id, 'path' => $path],
                    [
                        'alt_text' => "{$product->name} {$caption}",
                        'sort_order' => $sortOrder,
                        'is_primary' => false,
                    ],
                );
            }
        }
    }
}
