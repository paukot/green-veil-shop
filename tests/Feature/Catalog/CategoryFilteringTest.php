<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Effect;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\SkinType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('Category Catalog Filtering', function () {
    it('passes category-scoped facets and price range to Category view', function () {
        $category = Category::factory()->create(['slug' => 'serums', 'name' => 'Botanical Serums']);
        $otherCategory = Category::factory()->create(['slug' => 'creams', 'name' => 'Day Creams']);

        $brandA = Brand::factory()->create(['name' => 'Apothecary Lab', 'slug' => 'apothecary-lab']);
        $brandB = Brand::factory()->create(['name' => 'Nordic Flora', 'slug' => 'nordic-flora']);

        // Product in target category
        $productInCat = Product::factory()
            ->withCategory($category)
            ->create([
                'brand_id' => $brandA->id,
                'price' => 28.50,
                'classification' => 'dermocosmetic',
                'formula_type' => 'serum',
                'spf' => 'SPF 30',
                'manufactured_in' => 'FR',
                'is_active' => true,
            ]);

        // Product in other category with different brand
        Product::factory()
            ->withCategory($otherCategory)
            ->create([
                'brand_id' => $brandB->id,
                'price' => 95.00,
                'classification' => 'luxury',
                'is_active' => true,
            ]);

        $response = $this->get(route('categories.show', $category->slug));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Category')
            ->has('facets')
            ->has('facets.brands', 1)
            ->where('facets.brands.0.slug', 'apothecary-lab')
            ->where('facets.brands.0.products_count', 1)
            ->where('priceRange.min', 28.5)
            ->where('priceRange.max', 28.5)
            ->has('filters')
        );
    });

    it('filters category products by multiple relational facets simultaneously', function () {
        $category = Category::factory()->create();

        $drySkin = SkinType::create(['name' => 'Dry Skin', 'slug' => 'dry', 'sort_order' => 1]);
        $sensitiveSkin = SkinType::create(['name' => 'Sensitive Skin', 'slug' => 'sensitive', 'sort_order' => 2]);
        $oilySkin = SkinType::create(['name' => 'Oily Skin', 'slug' => 'oily', 'sort_order' => 3]);

        $hydratingEffect = Effect::create(['name' => 'Hydrating', 'slug' => 'hydrating', 'sort_order' => 1]);
        $mattifyingEffect = Effect::create(['name' => 'Mattifying', 'slug' => 'mattifying', 'sort_order' => 2]);

        $hyaluronic = Ingredient::create(['name' => 'Hyaluronic Acid', 'slug' => 'hyaluronic-acid', 'sort_order' => 1]);

        $veganClaim = Claim::create(['name' => 'Vegan', 'slug' => 'vegan', 'sort_order' => 1]);

        // Matching product: Dry skin, Hydrating, Hyaluronic acid, Vegan
        $matching = Product::factory()->withCategory($category)->create(['name' => 'Deep Moisture Elixir']);
        $matching->skinTypes()->attach([$drySkin->id, $sensitiveSkin->id]);
        $matching->effects()->attach($hydratingEffect->id);
        $matching->ingredients()->attach($hyaluronic->id);
        $matching->claims()->attach($veganClaim->id);

        // Non-matching product: Oily skin, Mattifying
        $nonMatching = Product::factory()->withCategory($category)->create(['name' => 'Pore Refining Tonic']);
        $nonMatching->skinTypes()->attach($oilySkin->id);
        $nonMatching->effects()->attach($mattifyingEffect->id);

        $response = $this->get(route('categories.show', [
            'category' => $category->slug,
            'skin_types' => 'dry',
            'effects' => 'hydrating',
            'ingredients' => 'hyaluronic-acid',
            'claims' => 'vegan',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Category')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Deep Moisture Elixir')
        );
    });

    it('filters category products by scalar cosmetic attributes', function () {
        $category = Category::factory()->create();

        $productA = Product::factory()->withCategory($category)->create([
            'name' => 'Mineral Sun Essence',
            'classification' => 'dermocosmetic',
            'formula_type' => 'fluid',
            'gender' => 'unisex',
            'spf' => 'SPF 50+',
            'manufactured_in' => 'KR',
        ]);

        $productB = Product::factory()->withCategory($category)->create([
            'name' => 'Botanical Night Balm',
            'classification' => 'natural',
            'formula_type' => 'balm',
            'gender' => 'women',
            'spf' => null,
            'manufactured_in' => 'FR',
        ]);

        // Filter by SPF and Origin
        $response = $this->get(route('categories.show', [
            'category' => $category->slug,
            'spf' => 'SPF 50+',
            'manufactured_in' => 'KR',
            'classification' => 'dermocosmetic',
            'formula_type' => 'fluid',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Category')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Mineral Sun Essence')
        );
    });
});
