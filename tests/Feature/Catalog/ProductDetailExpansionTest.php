<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Effect;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SkinType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('Product Detail Expansion', function () {
    it('serializes all cosmetic attributes, brand, variants, and lookups on product show', function () {
        $category = Category::factory()->create(['name' => 'Eye Care', 'slug' => 'eye-care']);
        $brand = Brand::factory()->create(['name' => 'Botanica Lux', 'slug' => 'botanica-lux', 'logo_path' => 'brands/logo.png']);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'series' => 'Rose Regenerative',
            'name' => 'Rose Infused Eye Cream',
            'sku' => 'GV-EYE-001',
            'barcode' => '5901234567890',
            'price' => 30.79,
            'compare_at_price' => 34.79,
            'volume' => 50.00,
            'unit' => 'ml',
            'unit_price_base_amount' => 100,
            'classification' => 'dermocosmetic',
            'gender' => 'unisex',
            'formula_type' => 'cream',
            'spf' => 'SPF 15',
            'manufactured_in' => 'FR',
            'pao_months' => 6,
            'responsible_person' => 'Green Veil EU Ltd, Dublin, Ireland',
            'inci_list' => 'Aqua, Glycerin, Rosa Damascena Flower Water, Cetearyl Alcohol.',
            'how_to_use' => 'Apply a pea-sized amount around the orbital bone morning and evening.',
            'warnings' => 'For external use only. Discontinue if redness occurs.',
            'custom_characteristics' => [
                'Applicator' => 'Cooling Ceramic Tip',
                'Finish' => 'Velvet Matte',
            ],
            'is_active' => true,
        ]);

        $variantA = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '01 Light Beige',
            'variant_type' => 'color',
            'color_hex' => '#E8C5A5',
            'sku' => 'GV-EYE-001-01',
            'price' => 30.79,
            'is_default' => true,
            'sort_order' => 1,
        ]);

        $variantB = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '02 Warm Honey',
            'variant_type' => 'color',
            'color_hex' => '#C4946E',
            'sku' => 'GV-EYE-001-02',
            'price' => 32.79,
            'is_default' => false,
            'sort_order' => 2,
        ]);

        $skinType = SkinType::create(['name' => 'Sensitive Skin', 'slug' => 'sensitive', 'sort_order' => 1]);
        $effect = Effect::create(['name' => 'Depuffing', 'slug' => 'depuffing', 'sort_order' => 1]);
        $ingredient = Ingredient::create(['name' => 'Caffeine', 'slug' => 'caffeine', 'sort_order' => 1]);
        $claim = Claim::create(['name' => 'Ophthalmologist Tested', 'slug' => 'ophthalmologist-tested', 'sort_order' => 1]);

        $product->skinTypes()->attach($skinType->id);
        $product->effects()->attach($effect->id);
        $product->ingredients()->attach($ingredient->id);
        $product->claims()->attach($claim->id);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Product')
            ->where('product.name', 'Rose Infused Eye Cream')
            ->where('product.series', 'Rose Regenerative')
            ->where('product.barcode', '5901234567890')
            ->where('product.brand.name', 'Botanica Lux')
            ->where('product.brand.slug', 'botanica-lux')
            ->where('product.volume', 50)
            ->where('product.unit', 'ml')
            ->where('product.unit_price_base_amount', 100)
            ->where('product.formatted_unit_price', '61.58 € / 100 ml')
            ->where('product.classification', 'dermocosmetic')
            ->where('product.spf', 'SPF 15')
            ->where('product.pao_months', 6)
            ->where('product.manufactured_in', 'FR')
            ->where('product.responsible_person', 'Green Veil EU Ltd, Dublin, Ireland')
            ->where('product.inci_list', 'Aqua, Glycerin, Rosa Damascena Flower Water, Cetearyl Alcohol.')
            ->where('product.custom_characteristics.Applicator', 'Cooling Ceramic Tip')
            ->has('product.variants', 2)
            ->where('product.variants.0.name', '01 Light Beige')
            ->where('product.variants.0.color_hex', '#E8C5A5')
            ->has('product.skin_types', 1)
            ->where('product.skin_types.0.name', 'Sensitive Skin')
            ->has('product.effects', 1)
            ->where('product.effects.0.name', 'Depuffing')
            ->has('product.ingredients', 1)
            ->where('product.ingredients.0.name', 'Caffeine')
            ->has('product.claims', 1)
            ->where('product.claims.0.name', 'Ophthalmologist Tested')
        );
    });
});
