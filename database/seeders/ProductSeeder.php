<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Effect;
use App\Models\HairType;
use App\Models\Ingredient;
use App\Models\SkinType;
use Database\Seeders\Support\SeedImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Product Series per Brand for cosmetic lines.
     *
     * @var array<string, list<string>>
     */
    protected const BRAND_SERIES = [
        'Maybelline New York' => ['Lash Sensational', 'SuperStay', 'Fit Me', 'Lifter Gloss', 'Colossal'],
        'L\'Oréal Paris' => ['Revitalift Laser', 'Infaillible', 'Telescopic', 'Elvive Bond Repair', 'Age Perfect'],
        'CeraVe' => ['Moisturising', 'SA Smoothing', 'Hydrating Cleanser', 'Blemish Control', 'Eye Repair'],
        'La Roche-Posay' => ['Effaclar', 'Cicaplast B5', 'Anthelios', 'Hyalu B5', 'Toleriane Ultra'],
        'The Ordinary' => ['Direct Acids', 'Hydrators & Oils', 'More Molecules', 'Peptides & Retinoids', 'Serum Foundation'],
        'Green Veil Organics' => ['Radiance Boost', 'Barrier Therapy', 'Pure Petal', 'Nordic Pine', 'Satin Bloom'],
        'COSRX' => ['Advanced Snail 96', 'Low pH Good Morning', 'AHA/BHA Clarifying', 'Propolis Light', 'Master Patch'],
        'Rituals' => ['The Ritual of Sakura', 'The Ritual of Ayurveda', 'The Ritual of Karma', 'The Ritual of Jing', 'The Ritual of Mehr'],
        'Bioderma' => ['Sensibio H2O', 'Sebium Hydra', 'Atoderm Intensive', 'Photoderm Max', 'Hydrabio'],
        'Kiehl\'s' => ['Ultra Facial', 'Midnight Recovery', 'Calendula Herbal', 'Rare Earth', 'Clearly Corrective'],
        'NYX Professional Makeup' => ['Fat Oil', 'Marshmellow', 'Epic Ink', 'Butter Gloss', 'Bare With Me'],
        'Vichy Laboratories' => ['Mineral 89', 'Liftactiv Specialist', 'Dercos Anti-Dandruff', 'Normaderm Phytosolution', 'Capital Soleil'],
    ];

    /**
     * Realistic EU INCI chemical formulations by cosmetic domain.
     *
     * @var array<string, string>
     */
    protected const INCI_FORMULATIONS = [
        'makeup' => 'Aqua / Water, Propylene Glycol, Styrene/Acrylates/Ammonium Methacrylate Copolymer, Polyurethane-35, Cera Alba / Beeswax, Synthetic Fluorphlogopite, Glyceryl Stearate, Cetyl Alcohol, Copernicia Cerifera Cera, Stearic Acid, Palmitic Acid, Aminomethyl Propanediol, Glycerin, Hydroxyethylcellulose, Caprylyl Glycol, Bambusa Vulgaris Extract. [+/- May Contain: CI 77491, CI 77492, CI 77499, CI 77891, Mica].',
        'skincare' => 'Aqua / Water, Glycerin, Dimethicone, Niacinamide, Sodium Hyaluronate, Ceramide NP, Ceramide AP, Ceramide EOP, Phytosphingosine, Cholesterol, Tocopherol, Carbomer, Xanthan Gum, Phenoxyethanol, Ethylhexylglycerin, Disodium EDTA.',
        'hair' => 'Aqua / Water, Sodium Laureth Sulfate, Cocamidopropyl Betaine, Glycol Distearate, Sodium Chloride, Argania Spinosa Kernel Oil, Hydrolyzed Keratin, Biotin, Panthenol, Polyquaternium-10, Citric Acid, Sodium Benzoate, Parfum / Fragrance, Linalool, Hexyl Cinnamal.',
        'body-care' => 'Aqua, Butyrospermum Parkii (Shea) Butter, Cetearyl Alcohol, Caprylic/Capric Triglyceride, Glycerin, Prunus Amygdalus Dulcis (Sweet Almond) Oil, Mangifera Indica Seed Butter, Aloe Barbadensis Leaf Juice, Tocopheryl Acetate, Xanthan Gum, Potassium Sorbate, Sodium Benzoate, Citral, Limonene.',
        'perfumes' => 'Alcohol Denat., Parfum / Fragrance, Aqua / Water, Linalool, Limonene, Benzyl Salicylate, Hydroxycitronellal, Geraniol, Citronellol, Coumarin, Benzyl Alcohol, Citral.',
        'men' => 'Aqua, Glycerin, Stearic Acid, Palmitic Acid, Potassium Hydroxide, Cedrus Atlantica Bark Oil, Caffeine, Allantoin, Menthol, Tocopheryl Acetate, Melaleuca Alternifolia Leaf Oil, Parfum.',
        'accessories' => '100% Vegan synthetic Taklon fibers, recycled aluminum ferrule, sustainable FSC-certified birch wood handle.',
        'health-care' => 'Aqua, Hydrated Silica, Sorbitol, Glycerin, Xylitol, Sodium Cocoyl Glutamate, Mentha Piperita Oil, Charcoal Powder, Melaleuca Alternifolia Leaf Oil, Stevia Rebaudiana Extract.',
        'gifts' => 'Assorted luxury beauty formulations. Refer to individual cosmetic components inside the coffret for comprehensive INCI declarations.',
    ];

    /**
     * Cosmetic volume & package sizes by domain.
     *
     * @var array<string, array{volume: float, unit: string, base: int}>
     */
    protected const DOMAIN_VOLUMES = [
        'perfumes' => ['volume' => 50.00, 'unit' => 'ml', 'base' => 100],
        'makeup' => ['volume' => 7.20, 'unit' => 'ml', 'base' => 100],
        'skincare' => ['volume' => 30.00, 'unit' => 'ml', 'base' => 100],
        'hair' => ['volume' => 250.00, 'unit' => 'ml', 'base' => 100],
        'body-care' => ['volume' => 200.00, 'unit' => 'ml', 'base' => 100],
        'men' => ['volume' => 100.00, 'unit' => 'ml', 'base' => 100],
        'accessories' => ['volume' => 1.00, 'unit' => 'pcs', 'base' => 1],
        'health-care' => ['volume' => 75.00, 'unit' => 'ml', 'base' => 100],
        'gifts' => ['volume' => 1.00, 'unit' => 'pcs', 'base' => 1],
    ];

    /**
     * Realistic color cosmetic shades with authentic HEX swatch values.
     *
     * @var list<array{name: string, hex: string}>
     */
    protected const COLOR_SHADES = [
        ['name' => '01 Very Black', 'hex' => '#111111'],
        ['name' => '02 Cosmic Black', 'hex' => '#080808'],
        ['name' => '03 Burgundy Haze', 'hex' => '#4a1525'],
        ['name' => '04 Blue Mist', 'hex' => '#1e3a5f'],
        ['name' => '05 Nude Petal', 'hex' => '#d29680'],
        ['name' => '06 Warm Caramel', 'hex' => '#b36b4c'],
        ['name' => '07 Dusty Rose', 'hex' => '#c26d7d'],
        ['name' => '08 Ruby Velvet', 'hex' => '#8f1d2e'],
        ['name' => '09 Deep Plum', 'hex' => '#52223c'],
        ['name' => '10 Sunlit Peach', 'hex' => '#e28f73'],
        ['name' => '11 Spiced Cinnamon', 'hex' => '#9e4a3b'],
        ['name' => '12 Frosted Champagne', 'hex' => '#dfc5a6'],
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
            'Bifida Ferment Microbiome Ampoule', 'Smoothing Glycolic Resurfacing Peel', 'Nourishing Facial Oil',
        ],
        'hair' => [
            'Bond Repair Moisture Shampoo', 'Nourishing Smoothing Conditioner', 'Deep Repair Butter Hair Mask',
            'Weightless Glossing Argan Hair Oil', 'Scalp Clarifying Exfoliating Scrub', 'Caffeine Fortifying Scalp Serum',
            'Thermal Defense Protective Styling Mist', 'Leave-In Hydrating Conditioning Milk', 'Texturizing Sea Salt Spray',
        ],
        'body-care' => [
            'Whipped Mango & Shea Body Butter', 'Velvet Hydrating Body Lotion', 'Aromatherapeutic Calming Shower Gel',
            'Dead Sea Mineral Exfoliating Scrub', 'Eucalyptus Magnesium Bath Crystals', 'Intensive Restorative Hand Cream',
            'Soothing Foot Balm', 'Aluminum-Free Probiotic Deodorant',
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
            'Whitening Enzyme Toothpaste', 'Hydrating Lavender Hand Sanitizer Mist',
            'Hydrolyzed Marine Collagen Peptides', 'Pure French Lavender Essential Oil',
        ],
        'gifts' => [
            'Complete Radiance Skincare Discovery Set', 'Luxury Velvet Lip Collection Box',
            'Artisan Fragrance Miniature Coffret', 'Weekend Spa Pampering Gift Box',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedImages = SeedImages::make();

        // 1. Fetch all child/leaf categories and all curated brands
        $categories = Category::query()->whereNotNull('parent_id')->with('parent')->get();
        if ($categories->isEmpty()) {
            $categories = Category::all();
        }

        $brands = Brand::all();
        if ($brands->isEmpty()) {
            $this->call(BrandSeeder::class);
            $brands = Brand::all();
        }

        $brandList = $brands->all();
        $brandCount = count($brandList);

        $now = Carbon::now();
        $totalProducts = 10000;
        $batchSize = 1000;

        $categoryList = $categories->all();
        $categoryCount = count($categoryList);
        $activeCount = count(self::ACTIVES);

        // Disable query log for speed
        DB::disableQueryLog();

        $batches = [];

        // Lookup maps for pivot associations
        $skinTypesBySlug = SkinType::pluck('id', 'slug')->all();
        $hairTypesBySlug = HairType::pluck('id', 'slug')->all();
        $effectsBySlug = Effect::pluck('id', 'slug')->all();
        $ingredientsBySlug = Ingredient::pluck('id', 'slug')->all();
        $claimsBySlug = Claim::pluck('id', 'slug')->all();

        $skinTypePivots = [];
        $hairTypePivots = [];
        $effectPivots = [];
        $ingredientPivots = [];
        $claimPivots = [];

        $existingProductCount = DB::table('products')->count();

        if ($existingProductCount === 0) {
            for ($i = 1; $i <= $totalProducts; $i++) {
                $catIndex = ($i - 1) % $categoryCount;
                /** @var Category $category */
                $category = $categoryList[$catIndex];

                // Resolve domain category key for imagery, volume, and INCI
                $domainKey = self::resolveDomainKey($category);

                /** @var Brand $brandObj */
                $brandObj = $brandList[$i % $brandCount];
                $brandName = $brandObj->name;
                $seriesPool = self::BRAND_SERIES[$brandName] ?? ['Daily Ritual', 'Advanced Care', 'Pro Solution'];
                $series = $seriesPool[$i % count($seriesPool)];

                $active = self::ACTIVES[($i * 3) % $activeCount];
                $formulations = self::FORMULATIONS[$domainKey] ?? self::FORMULATIONS['skincare'];
                $formulation = $formulations[($i * 7) % count($formulations)];

                $hasShade = in_array($domainKey, ['makeup', 'makeup-lips', 'makeup-eyes', 'makeup-face'], true);
                $shadeIndex = ($i * 5) % count(self::COLOR_SHADES);
                $shadeData = self::COLOR_SHADES[$shadeIndex];
                $shadeSuffix = $hasShade ? ' - '.$shadeData['name'] : '';

                $name = "{$brandName} {$series} {$formulation}{$shadeSuffix}";
                $sku = sprintf('GV-%s-%05d', strtoupper(substr($domainKey, 0, 3)), $i);
                $barcode = sprintf('590%010d', 1000000000 + $i);
                $slug = Str::slug("{$name}-{$sku}");

                // Pricing logic
                $basePrice = round((float) (8.50 + (($i * 17) % 120) + ((($i * 31) % 99) / 100)), 2);
                $hasDiscount = ($i % 3 === 0);
                $compareAtPrice = $hasDiscount ? round($basePrice * 1.25, 2) : null;

                // Inventory logic: 93% in stock, 7% out of stock
                $stockQuantity = ($i % 15 === 0) ? 0 : (($i * 11) % 180 + 5);

                // Images
                $mainImage = $seedImages->productImage($domainKey, $sku);

                // Featured flag for the first 150 items
                $isFeatured = ($i <= 150);

                // Volume & Unit specs
                $volSpec = self::DOMAIN_VOLUMES[$domainKey] ?? ['volume' => 50.00, 'unit' => 'ml', 'base' => 100];

                // Cosmetic classification
                $classifications = ['drugstore', 'dermocosmetic', 'natural_organic', 'luxury', 'professional'];
                $classification = $classifications[$i % count($classifications)];

                // Target audience
                $gender = match ($domainKey) {
                    'men' => 'men',
                    'makeup' => 'women',
                    default => ($i % 5 === 0) ? 'women' : 'unisex',
                };

                // Domain profiles - Relational lookups
                $targetSkinSlugs = match ($domainKey) {
                    'skincare' => ['all-skin-types', 'sensitive', 'dry'],
                    'makeup' => ['all-skin-types', 'sensitive-eyes'],
                    'men' => ['sensitive', 'normal'],
                    'body-care' => ['all-skin-types', 'dry'],
                    default => ['all-skin-types'],
                };
                foreach ($targetSkinSlugs as $skinSlug) {
                    if (isset($skinTypesBySlug[$skinSlug])) {
                        $skinTypePivots[] = ['product_id' => $i, 'skin_type_id' => $skinTypesBySlug[$skinSlug]];
                    }
                }

                if ($domainKey === 'hair') {
                    foreach (['all-hair-types', 'dry-dehydrated', 'damaged-brittle'] as $hairSlug) {
                        if (isset($hairTypesBySlug[$hairSlug])) {
                            $hairTypePivots[] = ['product_id' => $i, 'hair_type_id' => $hairTypesBySlug[$hairSlug]];
                        }
                    }
                }

                $targetEffectSlugs = match ($domainKey) {
                    'makeup' => ['volumizing', 'lengthening', 'long-lasting'],
                    'skincare' => ['moisturizing', 'anti-aging', 'nourishing'],
                    'hair' => ['strengthening', 'radiance-glow', 'nourishing'],
                    'body-care' => ['moisturizing', 'soothing', 'firming'],
                    default => ['nourishing', 'soothing'],
                };
                foreach ($targetEffectSlugs as $effectSlug) {
                    if (isset($effectsBySlug[$effectSlug])) {
                        $effectPivots[] = ['product_id' => $i, 'effect_id' => $effectsBySlug[$effectSlug]];
                    }
                }

                $domainIngSlugs = match ($domainKey) {
                    'skincare' => ['hyaluronic-acid', 'niacinamide', 'vitamin-c', 'ceramides'],
                    'hair' => ['argan-oil', 'panthenol', 'jojoba-oil'],
                    'makeup' => ['bamboo-extract', 'vitamin-e'],
                    'body-care' => ['shea-butter', 'aloe-vera', 'squalane'],
                    default => ['panthenol', 'vitamin-e'],
                };
                $startIdx = $i % count($domainIngSlugs);
                $selectedIngs = array_slice($domainIngSlugs, $startIdx, 2);
                if (empty($selectedIngs)) {
                    $selectedIngs = [$domainIngSlugs[0]];
                }
                foreach ($selectedIngs as $ingSlug) {
                    if (isset($ingredientsBySlug[$ingSlug])) {
                        $ingredientPivots[] = ['product_id' => $i, 'ingredient_id' => $ingredientsBySlug[$ingSlug]];
                    }
                }

                $targetClaimSlugs = ['vegan', 'cruelty-free', 'dermatologist-tested'];
                if ($domainKey === 'makeup') {
                    $targetClaimSlugs[] = 'ophthalmologist-tested';
                }
                foreach ($targetClaimSlugs as $claimSlug) {
                    if (isset($claimsBySlug[$claimSlug])) {
                        $claimPivots[] = ['product_id' => $i, 'claim_id' => $claimsBySlug[$claimSlug]];
                    }
                }

                $customCharacteristics = json_encode([
                    'Applicator' => 'Precision Ergonomic',
                    'Texture' => 'Silky Fast-Absorbing',
                ]);

                $paoMonths = ($domainKey === 'makeup') ? 6 : (($domainKey === 'perfumes') ? 36 : 12);
                $manufacturedIn = $brandObj->country_of_origin ?: 'FR';
                $inciList = self::INCI_FORMULATIONS[$domainKey] ?? self::INCI_FORMULATIONS['skincare'];

                $batches[] = [
                    'id' => $i,
                    'category_id' => $category->id,
                    'brand_id' => $brandObj->id,
                    'series' => $series,
                    'sku' => $sku,
                    'barcode' => $barcode,
                    'slug' => $slug,
                    'price' => $basePrice,
                    'compare_at_price' => $compareAtPrice,
                    'volume' => $volSpec['volume'],
                    'unit' => $volSpec['unit'],
                    'unit_price_base_amount' => $volSpec['base'],
                    'classification' => $classification,
                    'gender' => $gender,
                    'age_range' => ($i % 4 === 0) ? '30+' : 'all',
                    'formula_type' => ($domainKey === 'skincare') ? 'serum' : (($domainKey === 'makeup') ? 'mousse' : 'liquid'),
                    'spf' => ($domainKey === 'skincare' && $i % 3 === 0) ? 'SPF 30' : 'none',
                    'custom_characteristics' => $customCharacteristics,
                    'inci_list' => $inciList,
                    'how_to_use' => 'Apply gently as part of your daily ritual. Suitable for morning and evening routines.',
                    'warnings' => 'For external cosmetic use only. Avoid direct eye contact. Store in a cool place between 5°C and 25°C.',
                    'manufactured_in' => $manufacturedIn,
                    'pao_months' => $paoMonths,
                    'responsible_person' => "{$brandName} Europe, 14 Rue Royale 75008 Paris",
                    'stock_quantity' => $stockQuantity,
                    'track_inventory' => true,
                    'is_active' => true,
                    'is_featured' => $isFeatured,
                    'sort_order' => $i % 100,
                    'main_image' => $mainImage,
                    'name' => $name,
                    'short_description' => "Enriched with {$active} to deliver visible radiance, nourishment, and professional results.",
                    'description' => "Crafted by {$brandName}, this clean beauty essential features {$active} in a dermatologist-tested, hypoallergenic formula. Designed for daily rituals, it revitalizes the skin barrier without synthetic parabens, phthalates, or harsh sulfates. 100% vegan and cruelty-free.",
                    'meta_title' => "{$name} | Green Veil Clean Cosmetics",
                    'meta_description' => "Shop {$name} by {$brandName}. Dermatologically formulated with {$active} for exceptional beauty care.",
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

            // Insert relational lookups in chunks after products are guaranteed to exist
            foreach (array_chunk($skinTypePivots, 2000) as $chunk) {
                DB::table('product_skin_type')->insert($chunk);
            }
            foreach (array_chunk($hairTypePivots, 2000) as $chunk) {
                DB::table('product_hair_type')->insert($chunk);
            }
            foreach (array_chunk($effectPivots, 2000) as $chunk) {
                DB::table('product_effect')->insert($chunk);
            }
            foreach (array_chunk($ingredientPivots, 2000) as $chunk) {
                DB::table('product_ingredient')->insert($chunk);
            }
            foreach (array_chunk($claimPivots, 2000) as $chunk) {
                DB::table('product_claim')->insert($chunk);
            }
        }

        // 2. Add extra gallery images for the top 150 featured products
        $featuredProducts = DB::table('products')->where('is_featured', true)->take(150)->get(['id', 'category_id', 'name', 'sku']);
        $categoriesById = Category::with('parent')->get()->keyBy('id');
        $existingGalleryPairs = DB::table('product_images')->select('product_id', 'path')->get()
            ->mapWithKeys(fn ($row) => ["{$row->product_id}:{$row->path}" => true])
            ->all();

        $imageBatches = [];
        foreach ($featuredProducts as $product) {
            $cat = $categoriesById->get($product->category_id);
            $domainKey = $cat ? self::resolveDomainKey($cat) : 'skincare';

            // 1) Alternate formula & texture shot (offsets 1 and 2 never repeat the main image)
            $path1 = $seedImages->productImage($domainKey, $product->sku, 1);
            if ($path1 !== null && ! isset($existingGalleryPairs["{$product->id}:{$path1}"])) {
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

            // 2) Packaging & ritual shot
            $path2 = $seedImages->productImage($domainKey, $product->sku, 2);
            if ($path2 !== null && ! isset($existingGalleryPairs["{$product->id}:{$path2}"])) {
                $imageBatches[] = [
                    'product_id' => $product->id,
                    'path' => $path2,
                    'alt_text' => "{$product->name} packaging",
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

        // 3. Seed realistic product variants for top 200 products across makeup, perfumes & skincare
        $existingVariantCount = DB::table('product_variants')->count();
        if ($existingVariantCount <= 4) {
            $variantProducts = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->select('products.id', 'products.name', 'products.sku', 'products.price', 'categories.slug as category_slug')
                ->orderBy('products.id')
                ->take(300)
                ->get();

            $variantBatches = [];

            foreach ($variantProducts as $prod) {
                $slug = $prod->category_slug;
                $isColorDomain = str_contains($slug, 'lip') || str_contains($slug, 'mascara') || str_contains($slug, 'eye') || str_contains($slug, 'brow') || str_contains($slug, 'nail') || str_contains($slug, 'powder') || str_contains($slug, 'foundation');
                $isFragranceDomain = str_contains($slug, 'perfume') || str_contains($slug, 'fragrance');

                if ($isColorDomain) {
                    // Seed 4 color shade variants
                    $shades = array_slice(self::COLOR_SHADES, ($prod->id % 8), 4);
                    foreach ($shades as $idx => $shade) {
                        $variantBatches[] = [
                            'product_id' => $prod->id,
                            'name' => $shade['name'],
                            'variant_type' => 'color',
                            'color_hex' => $shade['hex'],
                            'sku' => "{$prod->sku}-SHADE-0".($idx + 1),
                            'barcode' => sprintf('590%010d', 2000000000 + $prod->id * 10 + $idx),
                            'price' => $prod->price,
                            'compare_at_price' => round($prod->price * 1.2, 2),
                            'stock_quantity' => ($prod->id + $idx) % 35 + 5,
                            'track_inventory' => true,
                            'is_active' => true,
                            'is_default' => $idx === 0,
                            'image' => $seedImages->shadeSwatch($shade['name'], $shade['hex']),
                            'sort_order' => $idx + 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                } elseif ($isFragranceDomain) {
                    // Seed size variants (30ml, 50ml, 100ml)
                    $sizes = [
                        ['name' => '30 ml', 'multiplier' => 0.75],
                        ['name' => '50 ml', 'multiplier' => 1.00],
                        ['name' => '100 ml', 'multiplier' => 1.60],
                    ];
                    foreach ($sizes as $idx => $size) {
                        $variantBatches[] = [
                            'product_id' => $prod->id,
                            'name' => $size['name'],
                            'variant_type' => 'size',
                            'color_hex' => null,
                            'sku' => "{$prod->sku}-".str_replace(' ', '', $size['name']),
                            'barcode' => sprintf('590%010d', 3000000000 + $prod->id * 10 + $idx),
                            'price' => round($prod->price * $size['multiplier'], 2),
                            'compare_at_price' => round($prod->price * $size['multiplier'] * 1.25, 2),
                            'stock_quantity' => ($prod->id + $idx) % 25 + 5,
                            'track_inventory' => true,
                            'is_active' => true,
                            'is_default' => $idx === 1,
                            'image' => null,
                            'sort_order' => $idx + 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (count($variantBatches) >= 1000) {
                    DB::table('product_variants')->insert($variantBatches);
                    $variantBatches = [];
                }
            }

            if (! empty($variantBatches)) {
                DB::table('product_variants')->insert($variantBatches);
            }
        }
    }

    /**
     * Map category to functional domain key.
     */
    public static function resolveDomainKey(Category $category): string
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
