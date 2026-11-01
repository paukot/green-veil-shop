<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SliderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('HomeController', function () {
    it('renders the home page with inertia component Home and expected prop structure', function () {
        $slider = SliderItem::factory()->visible()->create(['sort_order' => 1]);
        $promotion = Promotion::factory()->percentage(10)->create();
        $category = Category::factory()->create();
        $product = Product::factory()->featured()->withCategory($category)->create(['sort_order' => 1]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('heroSlides', 1)
            ->where('heroSlides.0.id', $slider->id)
            ->has('promotions', 1)
            ->where('promotions.0.id', $promotion->id)
            ->has('featuredCategories', 1)
            ->where('featuredCategories.0.id', $category->id)
            ->has('featuredProducts', 1)
            ->where('featuredProducts.0.id', $product->id)
        );
    });

    it('only includes visible slider items', function () {
        $visibleSlider = SliderItem::factory()->visible()->create(['title' => 'Visible Essence']);
        SliderItem::factory()->hidden()->create(['title' => 'Hidden Slide']);
        SliderItem::factory()->expired()->create(['title' => 'Expired Slide']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('heroSlides', 1)
            ->where('heroSlides.0.title', 'Visible Essence')
        );
    });

    it('only includes currently active promotions within valid date windows', function () {
        $activePromo = Promotion::factory()->create(['name' => 'Current Valid Promo']);
        Promotion::factory()->inactive()->create(['name' => 'Inactive Promo']);
        Promotion::factory()->expired()->create(['name' => 'Expired Promo']);
        Promotion::factory()->upcoming()->create(['name' => 'Future Promo']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('promotions', 1)
            ->where('promotions.0.name', 'Current Valid Promo')
        );
    });

    it('limits featured root categories to a maximum of 6', function () {
        $rootCategories = Category::factory()->count(8)->create();
        // Create child categories to ensure they are excluded from root featured
        Category::factory()->asChildOf($rootCategories->first())->count(3)->create();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('featuredCategories', 6)
        );
    });

    it('only includes active featured products up to a maximum of 8 ordered by sort_order', function () {
        $category = Category::factory()->create();

        // 9 active featured products
        for ($i = 1; $i <= 9; $i++) {
            Product::factory()->featured()->withCategory($category)->create([
                'sort_order' => $i,
                'name' => "Featured Product {$i}",
            ]);
        }

        // Inactive featured product
        Product::factory()->featured()->inactive()->withCategory($category)->create([
            'sort_order' => 0,
            'name' => 'Inactive Featured',
        ]);

        // Active non-featured product
        Product::factory()->withCategory($category)->create([
            'is_featured' => false,
            'sort_order' => 0,
            'name' => 'Standard Product',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('featuredProducts', 8)
            ->where('featuredProducts.0.name', 'Featured Product 1')
            ->where('featuredProducts.7.name', 'Featured Product 8')
        );
    });

    it('renders cleanly when database has no hero slides or promotions', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('heroSlides', 0)
            ->has('promotions', 0)
            ->has('featuredCategories', 0)
            ->has('featuredProducts', 0)
        );
    });
});
