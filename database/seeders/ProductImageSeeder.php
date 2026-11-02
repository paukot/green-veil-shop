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
    protected const GALLERY_POOLS = [
        'perfumes' => [
            'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1594035910387-fea47794261f?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1523293182086-7651a899d37f?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1595425970377-c9703cf48b6d?auto=format&fit=crop&w=600&q=80',
        ],
        'makeup' => [
            'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1625093742435-6fa192b6fb10?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1503236823255-94609f598e71?auto=format&fit=crop&w=600&q=80',
        ],
        'skincare' => [
            'https://images.unsplash.com/photo-1608248597359-0a6723c34898?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?auto=format&fit=crop&w=600&q=80',
        ],
        'hair' => [
            'https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1522337660859-02fbefca4702?auto=format&fit=crop&w=600&q=80',
        ],
        'body-care' => [
            'https://images.unsplash.com/photo-1556228722-d0b713912975?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1601049541289-9b1b7bbbfe19?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1616683693504-3ea7e9ad6fec?auto=format&fit=crop&w=600&q=80',
        ],
        'men' => [
            'https://images.unsplash.com/photo-1621607512214-68297480165e?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1621607512022-6aecc4fed814?auto=format&fit=crop&w=600&q=80',
        ],
    ];

    /**
     * Seed supplementary high-resolution product gallery images across catalog products.
     */
    public function run(): void
    {
        // Target products that don't have multiple gallery images yet
        $products = Product::query()
            ->with(['category.parent', 'images'])
            ->take(300)
            ->get();

        foreach ($products as $product) {
            if ($product->images->count() >= 2) {
                continue;
            }

            $catSlug = $product->category->slug ?? '';
            $parentSlug = $product->category->parent->slug ?? '';

            $domain = match (true) {
                $parentSlug === 'perfumes' || str_contains($catSlug, 'perfume') => 'perfumes',
                $parentSlug === 'hair' || str_contains($catSlug, 'hair') || str_contains($catSlug, 'shampoo') => 'hair',
                $parentSlug === 'body-care' || str_contains($catSlug, 'body') => 'body-care',
                $parentSlug === 'men' || str_contains($catSlug, 'men') || str_contains($catSlug, 'beard') => 'men',
                $parentSlug === 'makeup' || str_contains($catSlug, 'lip') || str_contains($catSlug, 'eye') => 'makeup',
                default => 'skincare',
            };

            $pool = self::GALLERY_POOLS[$domain];
            $poolCount = count($pool);

            ProductImage::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'path' => $pool[($product->id + 1) % $poolCount],
                ],
                [
                    'alt_text' => "{$product->name} formula & texture detail",
                    'sort_order' => 2,
                    'is_primary' => false,
                ],
            );

            ProductImage::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'path' => $pool[($product->id + 2) % $poolCount],
                ],
                [
                    'alt_text' => "{$product->name} botanical packaging detail",
                    'sort_order' => 3,
                    'is_primary' => false,
                ],
            );
        }
    }
}
