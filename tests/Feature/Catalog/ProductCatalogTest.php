<?php

use App\Models\Category;
use App\Models\Effect;
use App\Models\Product;
use App\Models\SkinType;
use Inertia\Testing\AssertableInertia as Assert;

describe('Product Catalog Index', function () {
    it('renders catalog index with active products and default pagination', function () {
        $category = Category::factory()->create(['name' => 'Botanical Serums']);
        Product::factory()->count(15)->withCategory($category)->create(['is_active' => true]);

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

    it('filters products by category parameter', function () {
        $skincare = Category::factory()->create(['name' => 'Skincare', 'slug' => 'skincare']);
        $bodycare = Category::factory()->create(['name' => 'Bodycare', 'slug' => 'bodycare']);

        Product::factory()->create([
            'category_id' => $skincare->id,
            'name' => 'Face Balm',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $bodycare->id,
            'name' => 'Body Butter',
            'is_active' => true,
        ]);

        $response = $this->get(route('products.index', ['category' => 'skincare']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Face Balm')
        );
    });

    it('filters products by search keyword q', function () {
        Product::factory()->create(['name' => 'Rose Hydrosol Mist', 'sku' => 'GV-ROSE-01', 'is_active' => true]);
        Product::factory()->create(['name' => 'Lavender Night Balm', 'sku' => 'GV-LAV-02', 'is_active' => true]);

        $response = $this->get(route('products.index', ['q' => 'Hydrosol']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Rose Hydrosol Mist')
            ->where('filters.q', 'Hydrosol')
        );
    });

    it('filters products by price range', function () {
        Product::factory()->create(['name' => 'Affordable Lip Salve', 'price' => 15.00, 'is_active' => true]);
        Product::factory()->create(['name' => 'Mid Botanical Serum', 'price' => 45.00, 'is_active' => true]);
        Product::factory()->create(['name' => 'Luxury Gold Elixir', 'price' => 120.00, 'is_active' => true]);

        $response = $this->get(route('products.index', ['min_price' => 20, 'max_price' => 60]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Mid Botanical Serum')
        );
    });

    it('filters products by in_stock availability', function () {
        Product::factory()->create([
            'name' => 'In Stock Toner',
            'stock_quantity' => 10,
            'track_inventory' => true,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name' => 'Sold Out Toner',
            'stock_quantity' => 0,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $response = $this->get(route('products.index', ['in_stock' => 'true']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'In Stock Toner')
        );
    });

    it('sorts products by price ascending, descending, and newest', function () {
        Product::factory()->create(['name' => 'Item A', 'price' => 10.00, 'created_at' => now()->subDays(2), 'is_active' => true]);
        Product::factory()->create(['name' => 'Item B', 'price' => 50.00, 'created_at' => now()->subDay(), 'is_active' => true]);
        Product::factory()->create(['name' => 'Item C', 'price' => 30.00, 'created_at' => now(), 'is_active' => true]);

        // Price asc
        $respAsc = $this->get(route('products.index', ['sort' => 'price_asc']));
        $respAsc->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('products.data.0.name', 'Item A')
            ->where('products.data.1.name', 'Item C')
            ->where('products.data.2.name', 'Item B')
        );

        // Price desc
        $respDesc = $this->get(route('products.index', ['sort' => 'price_desc']));
        $respDesc->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('products.data.0.name', 'Item B')
            ->where('products.data.1.name', 'Item C')
            ->where('products.data.2.name', 'Item A')
        );

        // Newest
        $respNew = $this->get(route('products.index', ['sort' => 'newest']));
        $respNew->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('products.data.0.name', 'Item C')
            ->where('products.data.1.name', 'Item B')
            ->where('products.data.2.name', 'Item A')
        );
    });

    it('filters products by skin type and effect tags', function () {
        $skinType = SkinType::create(['name' => 'Sensitive', 'slug' => 'sensitive']);
        $effect = Effect::create(['name' => 'Hydrating', 'slug' => 'hydrating']);

        $matchingProduct = Product::factory()->create(['name' => 'Sensitive Hydrating Gel', 'is_active' => true]);
        $matchingProduct->skinTypes()->attach($skinType);
        $matchingProduct->effects()->attach($effect);

        Product::factory()->create(['name' => 'Oily Clarifying Serum', 'is_active' => true]);

        $response = $this->get(route('products.index', [
            'skin_types' => 'sensitive',
            'effects' => 'hydrating',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Catalog/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Sensitive Hydrating Gel')
        );
    });
});
