<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('active category renders catalog category page with products', function () {
    $category = Category::factory()->create([
        'name' => 'Skincare Serums',
        'slug' => 'skincare-serums',
        'is_active' => true,
    ]);

    $activeProduct = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Glow Vitamin C Serum',
        'is_active' => true,
    ]);

    $inactiveProduct = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Archived Serum',
        'is_active' => false,
    ]);

    $response = $this->get(route('categories.show', $category));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Catalog/Category')
        ->has('category', fn (Assert $cat) => $cat
            ->where('id', $category->id)
            ->where('slug', 'skincare-serums')
            ->where('name', 'Skincare Serums')
            ->etc()
        )
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Glow Vitamin C Serum')
        ->has('breadcrumbs', 2)
    );
});

test('inactive category returns 404', function () {
    $category = Category::factory()->create([
        'name' => 'Secret Formula',
        'slug' => 'secret-formula',
        'is_active' => false,
    ]);

    $response = $this->get('/categories/secret-formula');

    $response->assertNotFound();
});

test('nonexistent category returns 404', function () {
    $response = $this->get('/categories/does-not-exist');

    $response->assertNotFound();
});

test('parent category displays products from child categories', function () {
    $parent = Category::factory()->create([
        'name' => 'Face Care',
        'slug' => 'face-care',
        'is_active' => true,
    ]);

    $child = Category::factory()->create([
        'parent_id' => $parent->id,
        'name' => 'Cleansers',
        'slug' => 'cleansers',
        'is_active' => true,
    ]);

    $parentProduct = Product::factory()->create([
        'category_id' => $parent->id,
        'name' => 'Face Tonic',
        'is_active' => true,
    ]);

    $childProduct = Product::factory()->create([
        'category_id' => $child->id,
        'name' => 'Foaming Cleanser',
        'is_active' => true,
    ]);

    $response = $this->get(route('categories.show', $parent));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Catalog/Category')
        ->has('products.data', 2)
        ->has('category.children', 1)
        ->where('category.children.0.name', 'Cleansers')
    );
});

test('child category breadcrumbs link back to parent', function () {
    $parent = Category::factory()->create([
        'name' => 'Body Care',
        'slug' => 'body-care',
        'is_active' => true,
    ]);

    $child = Category::factory()->create([
        'parent_id' => $parent->id,
        'name' => 'Scrubs',
        'slug' => 'scrubs',
        'is_active' => true,
    ]);

    $response = $this->get(route('categories.show', $child));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Catalog/Category')
        ->where('category.parent.name', 'Body Care')
        ->where('breadcrumbs.1.name', 'Body Care')
        ->where('breadcrumbs.2.name', 'Scrubs')
    );
});
