<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Effect;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SkinType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CosmeticProductEnrichmentSeeder extends Seeder
{
    /**
     * Seeds the flagship Maybelline Lash Sensational Sky High product exactly as presented on makeup.pl,
     * along with realistic variants and links existing products to curated brands.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Ensure Brand exists
        $brand = Brand::firstOrCreate(
            ['slug' => 'maybelline-new-york'],
            [
                'name' => 'Maybelline New York',
                'country_of_origin' => 'US',
                'website_url' => 'https://www.maybelline.com',
                'description' => 'Maybelline New York is the world\'s leading cosmetics brand, available in over 129 countries.',
                'logo_path' => 'brands/maybelline-new-york.png',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        // 2. Find mascara or makeup category
        $category = Category::where('slug', 'mascaras')
            ->orWhere('slug', 'eyes')
            ->orWhere('slug', 'makeup')
            ->first();

        if (! $category) {
            $category = Category::first();
        }

        // 3. Insert or update the Sky High mascara
        $skyHighSlug = 'maybelline-new-york-lash-sensational-sky-high';
        $skyHigh = Product::updateOrCreate(
            ['slug' => $skyHighSlug],
            [
                'category_id' => $category?->id,
                'brand_id' => $brand->id,
                'series' => 'Lash Sensational',
                'sku' => 'MAY-SKYHIGH-01',
                'barcode' => '3600531637996',
                'name' => 'Maybelline New York Lash Sensational Sky High',
                'price' => 30.79,
                'compare_at_price' => 31.79,
                'volume' => 7.20,
                'unit' => 'ml',
                'unit_price_base_amount' => 100,
                'classification' => 'drugstore',
                'gender' => 'women',
                'age_range' => 'all',
                'formula_type' => 'mousse',
                'spf' => 'none',
                'custom_characteristics' => [
                    'Szczoteczka' => 'Elastyczna silikonowa Flex Tower',
                    'Wodoodporność' => 'Zmywalna ciepłą wodą',
                    'Wykończenie' => 'Głęboka czerń z połyskiem',
                ],
                'short_description' => 'Maybelline New York Lash Sensational Sky High to tusz, który oferuje wyjątkowe efekty wydłużenia i zwiększenia objętości rzęs.',
                'description' => 'Maybelline New York Lash Sensational Sky High to innowacyjny tusz do rzęs wzbogacony ekstraktem z bambusa oraz mikrowłóknami, które wydłużają i pogrubiają rzęsy od nasady aż po same końce. Elastyczna silikonowa szczoteczka Flex Tower dociera do najdrobniejszych rzęs w kącikach oka, zapewniając równomierne pokrycie bez grudek i bez osypywania się przez cały dzień. Testowany oftalmologicznie, odpowiedni dla osób noszących szkła kontaktowe.',
                'inci_list' => 'G684936 1 - Ingredients: Aqua / Water / Eau, Propylene Glycol, Styrene/Acrylates/Ammonium Methacrylate Copolymer, Polyurethane-35, Cera Alba / Beeswax / Cire Dabeille, Synthetic Fluorphlogopite, Glyceryl Stearate, Cetyl Alcohol, PEG-200 Glyceryl Stearate, Ethylenediamine/Stearyl Dimer Dilinoleate Copolymer, Copernicia Cerifera Cera / Carnauba Wax / Cire De Carnauba, Stearic Acid, Palmitic Acid, Ethylene/VA Copolymer, Alcohol Denat., Paraffin, Aminomethyl Propanediol, Glycerin, Hydroxyethylcellulose, Phenoxyethanol, Caprylyl Glycol, Butylene Glycol, Xanthan Gum, Sodium Laureth Sulfate, Disodium EDTA, Myristic Acid, Tetrasodium EDTA, Pentaerythrityl Tetra-Di-T-Butyl Hydroxyhydrocinnamate, Potassium Sorbate, Silica, Bambusa Vulgaris Extract. [+/- May Contain: CI 77491, CI 77492, CI 77499 / Iron Oxides, CI 77007 / Ultramarines, Mica, CI 77891 / Titanium Dioxide, CI 75470 / Carmine, CI 77288 / Chromium Oxide Greens, CI 77742 / Manganese Violet, CI 77510 / Ferric Ferrocyanide]. F.I.L. D268567/1.',
                'how_to_use' => "Dla uzyskania najlepszych rezultatów przyłóż elastyczną szczoteczkę do rzęs i przeczesz je kilkukrotnie ruchem zygzakowatym od nasady ku końcom, aż do osiągnięcia pożądanej długości i objętości.\nNie pozwól tuszowi wyschnąć między kolejnymi warstwami.",
                'warnings' => 'Tylko do użytku zewnętrznego. W razie podrażnienia zaprzestać stosowania. Przechowywać w temperaturze od 5°C do 25°C.',
                'manufactured_in' => 'IT',
                'pao_months' => 6,
                'responsible_person' => 'Maybelline New York, 14 Rue Royale 75008 Paris, France',
                'stock_quantity' => 45,
                'track_inventory' => true,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
                'main_image' => 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=600&q=80',
                'meta_title' => 'Maybelline Lash Sensational Sky High Mascara | Green Veil',
                'meta_description' => 'Kup Maybelline Lash Sensational Sky High. Wydłużający i pogrubiający tusz do rzęs z ekstraktem z bambusa.',
            ]
        );

        // Sync cosmetic lookups for Sky High
        $skyHigh->skinTypes()->sync(SkinType::whereIn('slug', ['all-skin-types', 'sensitive-eyes'])->pluck('id'));
        $skyHigh->effects()->sync(Effect::whereIn('slug', ['volumizing', 'lengthening', 'lash-separation', 'long-lasting'])->pluck('id'));
        $skyHigh->ingredients()->sync(Ingredient::whereIn('slug', ['bamboo-extract'])->pluck('id'));
        $skyHigh->claims()->sync(Claim::whereIn('slug', ['ophthalmologist-tested', 'hypoallergenic'])->pluck('id'));

        // 4. Create shades / variants for Sky High (matching makeup.pl variants)
        $variants = [
            [
                'name' => '01 Very Black',
                'variant_type' => 'color',
                'color_hex' => '#121212',
                'sku' => 'MAY-SKYHIGH-01',
                'barcode' => '3600531637996',
                'price' => 30.79,
                'compare_at_price' => 31.79,
                'stock_quantity' => 25,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => '02 Cosmic Black',
                'variant_type' => 'color',
                'color_hex' => '#050505',
                'sku' => 'MAY-SKYHIGH-02',
                'barcode' => '3600531638009',
                'price' => 31.89,
                'compare_at_price' => 34.79,
                'stock_quantity' => 18,
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'name' => '03 Burgundy Haze',
                'variant_type' => 'color',
                'color_hex' => '#4a1525',
                'sku' => 'MAY-SKYHIGH-03',
                'barcode' => '3600531638016',
                'price' => 31.89,
                'compare_at_price' => 34.79,
                'stock_quantity' => 12,
                'is_default' => false,
                'sort_order' => 3,
            ],
            [
                'name' => '04 Blue Mist',
                'variant_type' => 'color',
                'color_hex' => '#1e3a5f',
                'sku' => 'MAY-SKYHIGH-04',
                'barcode' => '3600531638023',
                'price' => 31.89,
                'compare_at_price' => 34.79,
                'stock_quantity' => 15,
                'is_default' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($variants as $v) {
            ProductVariant::updateOrCreate(
                ['product_id' => $skyHigh->id, 'sku' => $v['sku']],
                [
                    'name' => $v['name'],
                    'variant_type' => $v['variant_type'],
                    'color_hex' => $v['color_hex'],
                    'barcode' => $v['barcode'],
                    'price' => $v['price'],
                    'compare_at_price' => $v['compare_at_price'],
                    'stock_quantity' => $v['stock_quantity'],
                    'track_inventory' => true,
                    'is_active' => true,
                    'is_default' => $v['is_default'],
                    'sort_order' => $v['sort_order'],
                ]
            );
        }

        // 5. Assign brands to existing products in database that don't have brand_id
        $allBrands = Brand::all();
        if ($allBrands->isNotEmpty()) {
            $brandIds = $allBrands->pluck('id')->all();
            $brandCount = count($brandIds);

            // Update in chunks
            Product::whereNull('brand_id')
                ->where('id', '!=', $skyHigh->id)
                ->chunkById(500, function ($products) use ($brandIds, $brandCount) {
                    foreach ($products as $p) {
                        $chosenBrandId = $brandIds[$p->id % $brandCount];
                        $p->update([
                            'brand_id' => $chosenBrandId,
                            'volume' => $p->volume ?: 50.00,
                            'unit' => $p->unit ?: 'ml',
                            'unit_price_base_amount' => $p->unit_price_base_amount ?: 100,
                            'classification' => $p->classification ?: 'natural_organic',
                            'pao_months' => $p->pao_months ?: 12,
                            'manufactured_in' => $p->manufactured_in ?: 'PL',
                        ]);
                    }
                });
        }
    }
}
