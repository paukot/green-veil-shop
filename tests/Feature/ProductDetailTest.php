<?php

use App\Enums\DiscountType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('active product renders detail page with gallery and details', function () {
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

    $image1 = ProductImage::factory()->create([
        'product_id' => $product->id,
        'path' => 'products/gallery/jojoba-1.jpg',
        'sort_order' => 1,
    ]);

    $image2 = ProductImage::factory()->create([
        'product_id' => $product->id,
        'path' => 'products/gallery/jojoba-2.jpg',
        'sort_order' => 2,
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
            ->has('images', 2)
            ->where('category.name', 'Elixirs')
            ->etc()
        )
        ->has('breadcrumbs', 4)
        ->where('breadcrumbs.2.name', 'Elixirs')
        ->where('breadcrumbs.3.name', 'Golden Jojoba Elixir')
    );
});

test('inactive product returns 404', function () {
    $product = Product::factory()->create([
        'name' => 'Draft Product',
        'slug' => 'draft-product',
        'is_active' => false,
    ]);

    $response = $this->get('/products/draft-product');

    $response->assertNotFound();
});

test('nonexistent product slug returns 404', function () {
    $response = $this->get('/products/non-existent-product');

    $response->assertNotFound();
});

test('product detail includes active promotions and excludes inactive ones', function () {
    $product = Product::factory()->create([
        'name' => 'Rose Face Cream',
        'slug' => 'rose-face-cream',
        'is_active' => true,
    ]);

    $activePromo = Promotion::create([
        'name' => 'Summer Glow',
        'code' => 'GLOW15',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 15,
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $inactivePromo = Promotion::create([
        'name' => 'Old Promo',
        'code' => 'OLD',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 50,
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

test('product detail includes related products from same category excluding current product and inactive products', function () {
    $category = Category::factory()->create();

    $mainProduct = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Main Product',
        'slug' => 'main-product',
        'is_active' => true,
    ]);

    $relatedActive = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Related Active Product',
        'is_active' => true,
    ]);

    $relatedInactive = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Related Inactive Product',
        'is_active' => false,
    ]);

    $otherCategoryProduct = Product::factory()->create([
        'name' => 'Other Category Product',
        'is_active' => true,
    ]);

    $response = $this->get(route('products.show', $mainProduct));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('relatedProducts', 1)
        ->where('relatedProducts.0.name', 'Related Active Product')
    );
});
