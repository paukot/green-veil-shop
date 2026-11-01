<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('CategoryController', function () {
    describe('show', function () {
        it('renders category catalog page with active products and breadcrumbs', function () {
            $category = Category::factory()->create([
                'name' => 'Botanical Toners',
                'slug' => 'botanical-toners',
            ]);

            Product::factory()->count(3)->withCategory($category)->create();

            $response = $this->get(route('categories.show', $category->slug));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Category')
                ->where('category.name', 'Botanical Toners')
                ->has('products.data', 3)
                ->has('breadcrumbs')
                ->has('filters')
            );
        });

        it('aborts with 404 when category is inactive', function () {
            $category = Category::factory()->inactive()->create(['slug' => 'secret-category']);

            $response = $this->get(route('categories.show', $category->slug));

            $response->assertNotFound();
        });

        it('aggregates products from the parent category and all active child categories', function () {
            $parent = Category::factory()->create(['name' => 'Skincare']);
            $childA = Category::factory()->asChildOf($parent)->create(['name' => 'Cleansers']);
            $childB = Category::factory()->asChildOf($parent)->create(['name' => 'Moisturizers']);

            Product::factory()->withCategory($parent)->create(['name' => 'Universal Botanical Mist']);
            Product::factory()->withCategory($childA)->create(['name' => 'Gentle Foam Cleanser']);
            Product::factory()->withCategory($childB)->create(['name' => 'Hydrating Day Cream']);

            $unrelatedCategory = Category::factory()->create();
            Product::factory()->withCategory($unrelatedCategory)->create(['name' => 'Bath Salts']);

            $response = $this->get(route('categories.show', $parent->slug));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Category')
                ->has('products.data', 3)
                ->where('products.data.0.name', fn ($name) => in_array($name, [
                    'Universal Botanical Mist',
                    'Gentle Foam Cleanser',
                    'Hydrating Day Cream',
                ]))
            );
        });

        it('excludes inactive child categories and inactive products', function () {
            $parent = Category::factory()->create(['name' => 'Haircare']);
            $activeChild = Category::factory()->asChildOf($parent)->create(['name' => 'Shampoos']);
            $inactiveChild = Category::factory()->asChildOf($parent)->inactive()->create(['name' => 'Secret Formulas']);

            Product::factory()->withCategory($activeChild)->create(['name' => 'Nettle Shampoo']);
            Product::factory()->withCategory($activeChild)->inactive()->create(['name' => 'Discontinued Wash']);
            Product::factory()->withCategory($inactiveChild)->create(['name' => 'Draft Product']);

            $response = $this->get(route('categories.show', $parent->slug));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Category')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Nettle Shampoo')
            );
        });

        it('applies filters for price range, in_stock, and sorting within the category', function () {
            $category = Category::factory()->create();

            $cheapInStock = Product::factory()->withCategory($category)->price(15.00)->inStock(10)->create(['name' => 'Affordable Mist']);
            $expensiveInStock = Product::factory()->withCategory($category)->price(75.00)->inStock(5)->create(['name' => 'Luxury Serum']);
            $outOfStock = Product::factory()->withCategory($category)->price(30.00)->outOfStock()->create(['name' => 'Sold Out Toner']);

            $response = $this->get(route('categories.show', [
                'category' => $category->slug,
                'min_price' => 10.00,
                'max_price' => 50.00,
                'in_stock' => '1',
                'sort' => 'price_asc',
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Category')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Affordable Mist')
            );
        });
    });
});
