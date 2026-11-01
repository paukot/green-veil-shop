<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('ProductController', function () {
    describe('index', function () {
        it('renders catalog index with active products and default pagination', function () {
            $category = Category::factory()->create(['name' => 'Botanical Serums']);
            Product::factory()->count(15)->withCategory($category)->create();

            $response = $this->get(route('products.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 12)
                ->where('products.total', 15)
                ->has('categories', 1)
                ->where('categories.0.name', 'Botanical Serums')
                ->has('priceRange')
                ->has('filters')
            );
        });

        it('does not display inactive products in the catalog or price range', function () {
            Product::factory()->create([
                'name' => 'Active Pure Cream',
                'price' => 45.00,
                'is_active' => true,
            ]);

            Product::factory()->inactive()->create([
                'name' => 'Secret Inactive Oil',
                'price' => 999.00,
            ]);

            $response = $this->get(route('products.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Active Pure Cream')
                ->where('priceRange.max', 45)
            );
        });

        it('filters products by search keyword q', function () {
            Product::factory()->create(['name' => 'Rose Hydrosol Mist', 'sku' => 'GV-ROSE-01']);
            Product::factory()->create(['name' => 'Lavender Night Balm', 'sku' => 'GV-LAV-02']);

            $response = $this->get(route('products.index', ['q' => 'Hydrosol']));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Rose Hydrosol Mist')
            );
        });

        it('filters products by category slug', function () {
            $catA = Category::factory()->create(['slug' => 'cleansers']);
            $catB = Category::factory()->create(['slug' => 'toners']);

            $productA = Product::factory()->withCategory($catA)->create(['name' => 'Clay Cleanser']);
            $productB = Product::factory()->withCategory($catB)->create(['name' => 'Herbal Toner']);

            $response = $this->get(route('products.index', ['category' => 'cleansers']));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Clay Cleanser')
            );
        });

        it('filters products by min_price and max_price', function () {
            Product::factory()->price(15.00)->create(['name' => 'Budget Balm']);
            Product::factory()->price(35.00)->create(['name' => 'Mid Tier Essence']);
            Product::factory()->price(80.00)->create(['name' => 'Luxury Elixir']);

            $response = $this->get(route('products.index', [
                'min_price' => 20.00,
                'max_price' => 50.00,
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Mid Tier Essence')
            );
        });

        it('filters products by in_stock availability', function () {
            Product::factory()->inStock(10)->create(['name' => 'Available Serum']);
            Product::factory()->outOfStock()->create(['name' => 'Sold Out Mask']);

            $response = $this->get(route('products.index', ['in_stock' => '1']));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Available Serum')
            );
        });

        it('sorts products by price_asc, price_desc, and newest', function () {
            $cheapest = Product::factory()->price(10.00)->create(['name' => 'Cheapest Toner']);
            $priciest = Product::factory()->price(90.00)->create(['name' => 'Priciest Serum']);

            // Test price_asc
            $responseAsc = $this->get(route('products.index', ['sort' => 'price_asc']));
            $responseAsc->assertOk();
            $responseAsc->assertInertia(fn (Assert $page) => $page
                ->where('products.data.0.name', 'Cheapest Toner')
            );

            // Test price_desc
            $responseDesc = $this->get(route('products.index', ['sort' => 'price_desc']));
            $responseDesc->assertOk();
            $responseDesc->assertInertia(fn (Assert $page) => $page
                ->where('products.data.0.name', 'Priciest Serum')
            );
        });

        it('includes active product count for each active category', function () {
            $category = Category::factory()->create();
            Product::factory()->count(3)->withCategory($category)->create(['is_active' => true]);
            Product::factory()->withCategory($category)->inactive()->create();

            $response = $this->get(route('products.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->where('categories.0.products_count', 3)
            );
        });
    });

    describe('show', function () {
        it('renders catalog product detail page with active promotions and images', function () {
            $category = Category::factory()->create(['name' => 'Face Oils']);
            $promo = Promotion::factory()->create(['name' => '10% Botanical Welcome']);
            $product = Product::factory()
                ->withCategory($category)
                ->withImages(3)
                ->create(['name' => 'Organic Rosehip Seed Oil']);

            $product->promotions()->attach($promo->id);

            $response = $this->get(route('products.show', $product->slug));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Product')
                ->where('product.name', 'Organic Rosehip Seed Oil')
                ->where('product.category.name', 'Face Oils')
                ->has('product.images', 3)
                ->has('product.promotions', 1)
                ->where('product.promotions.0.name', '10% Botanical Welcome')
                ->has('breadcrumbs')
                ->has('relatedProducts')
            );
        });

        it('aborts with 404 if product is inactive', function () {
            $product = Product::factory()->inactive()->create(['slug' => 'dormant-extract']);

            $response = $this->get(route('products.show', $product->slug));

            $response->assertNotFound();
        });

        it('loads related products from the same category excluding the current product', function () {
            $category = Category::factory()->create();
            $currentProduct = Product::factory()->withCategory($category)->create(['name' => 'Target Cream']);
            $siblingA = Product::factory()->withCategory($category)->create(['name' => 'Sibling A']);
            $siblingB = Product::factory()->withCategory($category)->create(['name' => 'Sibling B']);

            $otherCategory = Category::factory()->create();
            Product::factory()->withCategory($otherCategory)->create(['name' => 'Outsider Product']);

            $response = $this->get(route('products.show', $currentProduct->slug));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Product')
                ->has('relatedProducts', 2)
                ->where('relatedProducts.0.name', fn ($name) => in_array($name, ['Sibling A', 'Sibling B']))
            );
        });

        it('handles product without category gracefully', function () {
            $product = Product::factory()->withoutCategory()->create(['name' => 'Universal Botanical Balm']);

            $response = $this->get(route('products.show', $product->slug));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Product')
                ->where('product.name', 'Universal Botanical Balm')
                ->where('product.category', null)
                ->has('relatedProducts', 0)
            );
        });
    });
});
