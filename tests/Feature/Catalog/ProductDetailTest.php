<?php

use App\Enums\DiscountType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Effect;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\SkinType;
use Inertia\Testing\AssertableInertia as Assert;

describe('Product Detail Page', function () {
    it('renders detail page for active product with gallery, details, and breadcrumbs', function () {
        $category = Category::factory()->create([
            'name' => 'Elixirs',
            'slug' => 'elixirs',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Golden Jojoba Elixir',
            'slug' => 'golden-jojoba-elixir',
            'price' => 45.00,
            'compare_at_price' => 55.00,
            'stock_quantity' => 12,
            'is_active' => true,
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/gallery/jojoba-1.jpg',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Product')
            ->has('product', fn (Assert $p) => $p
                ->where('id', $product->id)
                ->where('name', 'Golden Jojoba Elixir')
                ->where('slug', 'golden-jojoba-elixir')
                ->where('price', 45)
                ->where('compare_at_price', 55)
                ->where('discount_percentage', 18)
                ->where('is_in_stock', true)
                ->has('images', 1)
                ->where('category.name', 'Elixirs')
                ->etc()
            )
            ->has('breadcrumbs')
        );
    });

    it('returns 404 for inactive or non-existent product', function () {
        $inactive = Product::factory()->inactive()->create(['slug' => 'hidden-elixir']);

        $this->get(route('products.show', $inactive))->assertNotFound();
        $this->get('/products/non-existent-product-slug')->assertNotFound();
    });

    it('includes active attached promotions on product show', function () {
        $product = Product::factory()->price(100.00)->create([
            'name' => 'Rose Face Cream',
            'is_active' => true,
        ]);

        $activePromo = Promotion::factory()->create([
            'name' => 'Summer Glow',
            'code' => 'GLOW15',
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 15,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $inactivePromo = Promotion::factory()->create([
            'name' => 'Old Promo',
            'code' => 'OLD',
            'is_active' => false,
        ]);

        $product->promotions()->attach([$activePromo->id, $inactivePromo->id]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('product.promotions', 1)
            ->where('product.promotions.0.code', 'GLOW15')
        );
    });

    it('includes related products from same category excluding current product and inactive products', function () {
        $category = Category::factory()->create();

        $mainProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Main Product',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Related Active Product',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Related Inactive Product',
            'is_active' => false,
        ]);

        $response = $this->get(route('products.show', $mainProduct));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('relatedProducts', 1)
            ->where('relatedProducts.0.name', 'Related Active Product')
        );
    });

    it('serializes all cosmetic attributes, brand, variants, and lookups on product show', function () {
        $category = Category::factory()->create(['name' => 'Eye Care', 'slug' => 'eye-care']);
        $brand = Brand::factory()->create(['name' => 'Botanica Lux', 'slug' => 'botanica-lux']);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'series' => 'Rose Regenerative',
            'name' => 'Rose Infused Eye Cream',
            'sku' => 'GV-EYE-001',
            'price' => 30.79,
            'volume' => 50.00,
            'unit' => 'ml',
            'classification' => 'dermocosmetic',
            'formula_type' => 'cream',
            'spf' => 'SPF 15',
            'manufactured_in' => 'FR',
            'pao_months' => 6,
            'inci_list' => 'Aqua, Glycerin, Rosa Damascena Flower Water.',
            'how_to_use' => 'Apply a pea-sized amount morning and evening.',
            'warnings' => 'For external use only.',
            'is_active' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '01 Light Beige',
            'price' => 30.79,
            'is_default' => true,
        ]);

        $skinType = SkinType::create(['name' => 'Mature', 'slug' => 'mature']);
        $effect = Effect::create(['name' => 'Firming', 'slug' => 'firming']);
        $ingredient = Ingredient::create(['name' => 'Hyaluronic Acid', 'slug' => 'hyaluronic-acid']);
        $claim = Claim::create(['name' => 'Cruelty Free', 'slug' => 'cruelty-free']);

        $product->skinTypes()->attach($skinType);
        $product->effects()->attach($effect);
        $product->ingredients()->attach($ingredient);
        $product->claims()->attach($claim);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Product')
            ->has('product', fn (Assert $p) => $p
                ->where('name', 'Rose Infused Eye Cream')
                ->where('brand.name', 'Botanica Lux')
                ->where('classification', 'dermocosmetic')
                ->where('formula_type', 'cream')
                ->where('spf', 'SPF 15')
                ->where('pao_months', 6)
                ->where('inci_list', 'Aqua, Glycerin, Rosa Damascena Flower Water.')
                ->has('variants', 1)
                ->has('skin_types', 1)
                ->has('effects', 1)
                ->has('ingredients', 1)
                ->has('claims', 1)
                ->etc()
            )
        );
    });
});
