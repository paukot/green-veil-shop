<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('search by product name returns matching active products', function () {
    $category = Category::factory()->create();

    $match = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Rose Damascena Facial Oil',
        'is_active' => true,
    ]);

    $other = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Eucalyptus Bath Soak',
        'is_active' => true,
    ]);

    $response = $this->get('/products?q=Damascena');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Catalog/Index')
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Rose Damascena Facial Oil')
        ->where('filters.q', 'Damascena')
    );
});

test('search by short description or SKU finds matching products', function () {
    $category = Category::factory()->create();

    $skuMatch = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Hydrating Toner',
        'sku' => 'GV-SKU-999',
        'is_active' => true,
    ]);

    $descMatch = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Clarifying Cleanser',
        'short_description' => 'Infused with organic witch hazel extracts',
        'is_active' => true,
    ]);

    $responseSku = $this->get('/products?q=GV-SKU-999');
    $responseSku->assertInertia(fn (Assert $page) => $page
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Hydrating Toner')
    );

    $responseDesc = $this->get('/products?q=witch+hazel');
    $responseDesc->assertInertia(fn (Assert $page) => $page
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Clarifying Cleanser')
    );
});

test('search query excludes inactive matching products', function () {
    $category = Category::factory()->create();

    $active = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Active Chamomile Balm',
        'is_active' => true,
    ]);

    $inactive = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Archived Chamomile Balm',
        'is_active' => false,
    ]);

    $response = $this->get('/products?q=Chamomile');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Active Chamomile Balm')
    );
});

test('search with no matching results returns empty data without error', function () {
    Product::factory()->create(['name' => 'Lavender Oil', 'is_active' => true]);

    $response = $this->get('/products?q=NonExistentKeywordXYZ');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Catalog/Index')
        ->has('products.data', 0)
        ->where('products.total', 0)
        ->where('filters.q', 'NonExistentKeywordXYZ')
    );
});
