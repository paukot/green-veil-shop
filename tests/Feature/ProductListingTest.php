<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('products index renders catalog page with active products', function () {
    $category = Category::factory()->create(['name' => 'Skincare', 'slug' => 'skincare']);

    $activeProduct = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Rose Petal Cream',
        'is_active' => true,
    ]);

    $inactiveProduct = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Draft Cream',
        'is_active' => false,
    ]);

    $response = $this->get(route('products.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Catalog/Index')
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Rose Petal Cream')
        ->has('categories', 1)
        ->where('categories.0.name', 'Skincare')
        ->where('categories.0.products_count', 1)
        ->has('priceRange')
    );
});

test('products can be filtered by category', function () {
    $skincare = Category::factory()->create(['name' => 'Skincare', 'slug' => 'skincare']);
    $bodycare = Category::factory()->create(['name' => 'Bodycare', 'slug' => 'bodycare']);

    $skinProduct = Product::factory()->create([
        'category_id' => $skincare->id,
        'name' => 'Face Balm',
        'is_active' => true,
    ]);

    $bodyProduct = Product::factory()->create([
        'category_id' => $bodycare->id,
        'name' => 'Body Butter',
        'is_active' => true,
    ]);

    $response = $this->get(route('products.index', ['category' => 'skincare']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Face Balm')
        ->where('filters.category', 'skincare')
    );
});

test('products can be filtered by price range', function () {
    $category = Category::factory()->create();

    $cheap = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Gentle Lip Balm',
        'price' => 8.00,
        'is_active' => true,
    ]);

    $mid = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Nourishing Serum',
        'price' => 35.00,
        'is_active' => true,
    ]);

    $expensive = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Luxury Botanical Elixir',
        'price' => 85.00,
        'is_active' => true,
    ]);

    // Test min_price
    $responseMin = $this->get(route('products.index', ['min_price' => 30]));
    $responseMin->assertInertia(fn (Assert $page) => $page
        ->has('products.data', 2)
        ->where('products.data.0.name', fn ($name) => in_array($name, ['Nourishing Serum', 'Luxury Botanical Elixir']))
    );

    // Test max_price
    $responseMax = $this->get(route('products.index', ['max_price' => 20]));
    $responseMax->assertInertia(fn (Assert $page) => $page
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Gentle Lip Balm')
    );

    // Test price window
    $responseWindow = $this->get(route('products.index', ['min_price' => 20, 'max_price' => 50]));
    $responseWindow->assertInertia(fn (Assert $page) => $page
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Nourishing Serum')
    );
});

test('products can be filtered by in_stock availability', function () {
    $category = Category::factory()->create();

    $inStock = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Available Clay Mask',
        'stock_quantity' => 10,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $outOfStock = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Sold Out Toner',
        'stock_quantity' => 0,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $response = $this->get(route('products.index', ['in_stock' => 1]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Available Clay Mask')
    );
});

test('products can be sorted by price ascending and descending', function () {
    $category = Category::factory()->create();

    $cheap = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'A Soap Bar',
        'price' => 5.00,
        'is_active' => true,
    ]);

    $expensive = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Z Face Oil',
        'price' => 95.00,
        'is_active' => true,
    ]);

    // Price Ascending
    $responseAsc = $this->get(route('products.index', ['sort' => 'price_asc']));
    $responseAsc->assertInertia(fn (Assert $page) => $page
        ->where('products.data.0.name', 'A Soap Bar')
        ->where('products.data.1.name', 'Z Face Oil')
    );

    // Price Descending
    $responseDesc = $this->get(route('products.index', ['sort' => 'price_desc']));
    $responseDesc->assertInertia(fn (Assert $page) => $page
        ->where('products.data.0.name', 'Z Face Oil')
        ->where('products.data.1.name', 'A Soap Bar')
    );
});

test('products can be searched by query term', function () {
    $category = Category::factory()->create();

    $lavender = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Lavender Calming Mist',
        'short_description' => 'A relaxing herbal infusion',
        'is_active' => true,
    ]);

    $citrus = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Citrus Awakening Scrub',
        'short_description' => 'Brightening morning polish',
        'is_active' => true,
    ]);

    $response = $this->get(route('products.index', ['q' => 'lavender']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Lavender Calming Mist')
        ->where('filters.q', 'lavender')
    );
});
