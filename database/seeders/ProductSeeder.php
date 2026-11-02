<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Curated royalty-free beauty photography (Unsplash License / CC0).
     * Categorized by cosmetic domain for hyper-realistic visual presentation.
     *
     * @var array<string, list<string>>
     */
    protected const CATEGORY_IMAGE_POOLS = [
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
            'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=600&q=80',
        ],
        'skincare' => [
            'https://images.unsplash.com/photo-1608248597359-0a6723c34898?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1556228722-d0b713912975?auto=format&fit=crop&w=600&q=80',
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
            'https://images.unsplash.com/photo-1519735777090-ec97162dc266?auto=format&fit=crop&w=600&q=80',
        ],
        'men' => [
            'https://images.unsplash.com/photo-1621607512214-68297480165e?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1621607512022-6aecc4fed814?auto=format&fit=crop&w=600&q=80',
        ],
        'accessories' => [
            'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?auto=format&fit=crop&w=600&q=80',
        ],
        'health-care' => [
            'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=600&q=80',
        ],
        'gifts' => [
            'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1513201099705-a9742e15a5bb?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=600&q=80',
        ],
    ];

    /**
     * Procedural Beauty Vocabulary Matrix
     */
    protected const BRANDS = [
        'Lumière Botanica', 'Velvet Aura', 'Pure Petal', 'Celestial Glow', 'Verde Silk',
        'Flora & Form', 'Radiant Dew', 'Herb & Essence', 'Bare Element', 'Atelier Rose',
        'Opaline Paris', 'Botanica Medica', 'Sol & Scent', 'Aura Botanics', 'Elysian Glow',
        'Nectar & Nacre', 'Maison Botanique', 'Vespera Beauty', 'Chroma Pure', 'Solis Lab',
    ];

    protected const ACTIVES = [
        'Hyaluronic Acid 2% + B5', 'Niacinamide 10% + Zinc', 'Encapsulated Retinol 0.5%',
        'Vitamin C 15% Brightening', 'Centella Asiatica Calming', 'Squalane & Ceramide Barrier',
        'Salicylic Acid 2% Clarifying', 'Rosehip & Bakuchiol Renewing', 'Damascus Rose & Peptides',
        'Green Tea Polyphenols', 'Golden Argan & Jojoba', 'Fermented Rice & Prebiotics',
        'Glycolic Acid 7% Resurfacing', 'CoQ10 Firming Complex', 'Marine Collagen Elixir',
    ];

    protected const FORMULATIONS = [
        'makeup' => [
            'Satin Velvet Matte Lipstick', 'Hydrating Lip Plumping Gloss', 'Nourishing Berry Lip Oil',
            'Ultra-Conditioning Tinted Balm', 'Waterproof Lengthening Mascara', 'Tubing Lash Volume Mascara',
            'Precision Liquid Eyeliner', 'Silk Eyeshadow Compact Palette', 'Weightless Luminous Foundation',
            'Full-Coverage Creamy Concealer', 'Soft-Focus Blurring Setting Powder', 'Dewy Mineral Cream Blush',
            'Sun-Kissed Matte Powder Bronzer', 'Champagne Glow Liquid Highlighter', 'Hydra-Shield Setting Mist',
            'Sculpting Micro Brow Pencil', 'Clear Laminating Brow Gel', 'UV/LED Gel Nail Lacquer',
            'Melting Camellia Cleansing Balm', 'Micellar Infused Cleansing Water',
        ],
        'skincare' => [
            'Hydrating Daily Barrier Cream', 'Gentle Amino Acid Foaming Cleanser', 'Centella Soothing Gel Moisturizer',
            'AHA/BHA Balancing Treatment Toner', 'Overnight Recovery Sleeping Mask', 'Peptide Infused Eye Cream',
            'Invisible Mineral Sunscreen SPF 50', 'Illuminating Facial Treatment Essence', 'Clarifying Detox Clay Mask',
            'Bifida Ferment Microbiome Ampoule', 'Smoothing Glycolic Resurfacing Peel', 'Cold-Pressed Botanical Facial Oil',
        ],
        'hair' => [
            'Bond Repair Moisture Shampoo', 'Nourishing Botanical Conditioner', 'Deep Repair Butter Hair Mask',
            'Weightless Glossing Argan Hair Oil', 'Scalp Clarifying Exfoliating Scrub', 'Caffeine Fortifying Scalp Serum',
            'Thermal Defense Protective Styling Mist', 'Leave-In Hydrating Conditioning Milk', 'Texturizing Sea Salt Spray',
        ],
        'body-care' => [
            'Whipped Mango & Shea Body Butter', 'Velvet Hydrating Body Lotion', 'Aromatherapeutic Calming Shower Gel',
            'Dead Sea Mineral Exfoliating Scrub', 'Eucalyptus Magnesium Bath Crystals', 'Intensive Restorative Hand Cream',
            'Soothing Botanical Foot Balm', 'Aluminum-Free Probiotic Deodorant',
        ],
        'perfumes' => [
            'Eau de Parfum 50ml', 'Eau de Parfum 100ml', 'Luxury Extrait de Parfum 30ml',
            'Artisanal Niche Fragrance 50ml', 'Refreshing Hair & Body Mist 150ml', 'Travel Pocket Solid Perfume 15g',
        ],
        'men' => [
            'Comfort Cooling Shave Cream', 'Soothing Post-Shave Hydrating Balm', 'Nourishing Cedarwood Beard Oil',
            'All-in-One Energizing Face Wash', 'Invigorating Daily Body Wash', 'Matte Finish Styling Clay Pomade',
        ],
        'accessories' => [
            '12-Piece Luxury Vegan Brush Set', 'Natural Rose Quartz Gua Sha Stone', 'Ergonomic Heated Eyelash Curler',
            'Waterproof Multi-Compartment Vanity Bag', 'Dual-Sided LED Magnifying Mirror', 'Mulberry Silk Sleeping Mask',
        ],
        'health-care' => [
            'Botanical Whitening Enzyme Toothpaste', 'Hydrating Lavender Hand Sanitizer Mist',
            'Hydrolyzed Marine Collagen Peptides', 'Pure French Lavender Essential Oil',
        ],
        'gifts' => [
            'Complete Radiance Skincare Discovery Set', 'Luxury Velvet Lip Collection Box',
            'Artisan Fragrance Miniature Coffret', 'Weekend Spa Pampering Gift Box',
        ],
    ];

    protected const SHADES = [
        '01 Petal Nude', '02 Honey Caramel', '03 Dusty Rose', '04 Warm Terracotta',
        '05 Ruby Velvet', '06 Deep Plum', '07 Sunlit Peach', '08 Frosted Mauve',
        '09 Golden Bronze', '10 Crystal Sheer', '11 Spiced Cinnamon', '12 Rosewood Bliss',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Fetch all child/leaf categories and category groups
        $categories = Category::query()->whereNotNull('parent_id')->with('parent')->get();
        if ($categories->isEmpty()) {
            $categories = Category::all();
        }

        $now = Carbon::now();
        $totalProducts = 10000;
        $batchSize = 1000;

        $categoryList = $categories->all();
        $categoryCount = count($categoryList);

        $brandCount = count(self::BRANDS);
        $activeCount = count(self::ACTIVES);
        $shadeCount = count(self::SHADES);

        // Disable query log and foreign key checks for speed
        DB::disableQueryLog();

        $batches = [];
        $imageBatches = [];

        $existingProductCount = DB::table('products')->count();

        if ($existingProductCount === 0) {
            for ($i = 1; $i <= $totalProducts; $i++) {
                $catIndex = ($i - 1) % $categoryCount;
                /** @var Category $category */
                $category = $categoryList[$catIndex];

                // Resolve domain category key for imagery and vocabulary
                $domainKey = $this->resolveDomainKey($category);

                $brand = self::BRANDS[$i % $brandCount];
                $active = self::ACTIVES[($i * 3) % $activeCount];
                $formulations = self::FORMULATIONS[$domainKey] ?? self::FORMULATIONS['skincare'];
                $formulation = $formulations[($i * 7) % count($formulations)];

                // Include shades for makeup or lip/eye categories
                $hasShade = in_array($domainKey, ['makeup', 'makeup-lips', 'makeup-eyes', 'makeup-face'], true);
                $shade = $hasShade ? ' - '.self::SHADES[($i * 5) % $shadeCount] : '';

                $name = "{$brand} {$active} {$formulation}{$shade}";
                $sku = sprintf('GV-%s-%05d', strtoupper(substr($domainKey, 0, 3)), $i);
                $slug = Str::slug("{$name}-{$sku}");

                // Pricing logic
                $basePrice = round((float) (8.50 + (($i * 17) % 120) + ((($i * 31) % 99) / 100)), 2);
                $hasDiscount = ($i % 3 === 0);
                $compareAtPrice = $hasDiscount ? round($basePrice * 1.25, 2) : null;

                // Inventory logic: 93% in stock, 7% out of stock
                $stockQuantity = ($i % 15 === 0) ? 0 : (($i * 11) % 180 + 5);

                // Images
                $imagePool = self::CATEGORY_IMAGE_POOLS[$domainKey] ?? self::CATEGORY_IMAGE_POOLS['skincare'];
                $mainImage = $imagePool[($i * 3) % count($imagePool)];

                // Featured flag for the first 150 items
                $isFeatured = ($i <= 150);

                $batches[] = [
                    'category_id' => $category->id,
                    'sku' => $sku,
                    'slug' => $slug,
                    'price' => $basePrice,
                    'compare_at_price' => $compareAtPrice,
                    'stock_quantity' => $stockQuantity,
                    'track_inventory' => true,
                    'is_active' => true,
                    'is_featured' => $isFeatured,
                    'sort_order' => $i % 100,
                    'main_image' => $mainImage,
                    'name' => $name,
                    'short_description' => "Enriched with {$active} to deliver visible radiance, nourishment, and professional results.",
                    'description' => "Crafted by {$brand}, this clean beauty essential features {$active} in a dermatologist-tested, hypoallergenic formula. Designed for daily rituals, it revitalizes the skin barrier without synthetic parabens, phthalates, or harsh sulfates. 100% vegan and cruelty-free.",
                    'meta_title' => "{$name} | Green Veil Clean Cosmetics",
                    'meta_description' => "Shop {$name} by {$brand}. Dermatologically formulated with {$active} for exceptional beauty care.",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batches) >= $batchSize) {
                    DB::table('products')->insert($batches);
                    $batches = [];
                }
            }

            if (! empty($batches)) {
                DB::table('products')->insert($batches);
                $batches = [];
            }
        }

        // 2. Add extra gallery images for the top 150 featured products
        $featuredProducts = DB::table('products')->where('is_featured', true)->take(150)->get(['id', 'category_id', 'name']);
        $categoriesById = Category::with('parent')->get()->keyBy('id');
        $existingGalleryPairs = DB::table('product_images')->select('product_id', 'path')->get()
            ->mapWithKeys(fn ($row) => ["{$row->product_id}:{$row->path}" => true])
            ->all();

        foreach ($featuredProducts as $product) {
            $cat = $categoriesById->get($product->category_id);
            $domainKey = $cat ? $this->resolveDomainKey($cat) : 'skincare';
            $imagePool = self::CATEGORY_IMAGE_POOLS[$domainKey] ?? self::CATEGORY_IMAGE_POOLS['skincare'];
            $poolCount = count($imagePool);

            // 1) Alternate formula & texture shot
            $path1 = $imagePool[($product->id + 1) % $poolCount];
            if (! isset($existingGalleryPairs["{$product->id}:{$path1}"])) {
                $imageBatches[] = [
                    'product_id' => $product->id,
                    'path' => $path1,
                    'alt_text' => "{$product->name} texture & formula detail",
                    'sort_order' => 1,
                    'is_primary' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $existingGalleryPairs["{$product->id}:{$path1}"] = true;
            }

            // 2) Botanical packaging & ritual shot
            $path2 = $imagePool[($product->id + 2) % $poolCount];
            if (! isset($existingGalleryPairs["{$product->id}:{$path2}"])) {
                $imageBatches[] = [
                    'product_id' => $product->id,
                    'path' => $path2,
                    'alt_text' => "{$product->name} botanical packaging",
                    'sort_order' => 2,
                    'is_primary' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $existingGalleryPairs["{$product->id}:{$path2}"] = true;
            }
        }

        if (! empty($imageBatches)) {
            DB::table('product_images')->insert($imageBatches);
        }
    }

    /**
     * Map category to functional domain key.
     */
    protected function resolveDomainKey(Category $category): string
    {
        $parentSlug = $category->parent->slug ?? '';
        $slug = $category->slug;

        if ($parentSlug === 'perfumes' || str_contains($slug, 'perfume')) {
            return 'perfumes';
        }

        if ($parentSlug === 'hair' || str_contains($slug, 'hair') || str_contains($slug, 'shampoo')) {
            return 'hair';
        }

        if ($parentSlug === 'body-care' || str_contains($slug, 'body') || str_contains($slug, 'bath')) {
            return 'body-care';
        }

        if ($parentSlug === 'men' || str_contains($slug, 'men') || str_contains($slug, 'beard')) {
            return 'men';
        }

        if ($parentSlug === 'accessories' || str_contains($slug, 'brush') || str_contains($slug, 'tool')) {
            return 'accessories';
        }

        if ($parentSlug === 'health-care') {
            return 'health-care';
        }

        if ($parentSlug === 'gifts') {
            return 'gifts';
        }

        if ($parentSlug === 'makeup' || str_contains($slug, 'lip') || str_contains($slug, 'mascara') || str_contains($slug, 'eye') || str_contains($slug, 'brow') || str_contains($slug, 'nail') || str_contains($slug, 'palette')) {
            return 'makeup';
        }

        return 'skincare';
    }
}
