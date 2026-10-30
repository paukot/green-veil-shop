<?php

use App\Enums\DiscountType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SliderItem;

test('home page renders successfully with home component', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Home'));
});

test('home page displays active slider items and excludes inactive or expired ones', function () {
    $activeSlide = SliderItem::create([
        'image' => 'sliders/active.jpg',
        'title' => 'Active Botanical Slide',
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $inactiveSlide = SliderItem::create([
        'image' => 'sliders/inactive.jpg',
        'title' => 'Inactive Slide',
        'is_active' => false,
    ]);

    $expiredSlide = SliderItem::create([
        'image' => 'sliders/expired.jpg',
        'title' => 'Expired Slide',
        'is_active' => true,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->subDay(),
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('heroSlides', fn ($slides) => collect($slides)->contains('id', $activeSlide->id)
            && ! collect($slides)->contains('id', $inactiveSlide->id)
            && ! collect($slides)->contains('id', $expiredSlide->id)
        )
    );
});

test('home page displays active promotions and excludes inactive or expired ones', function () {
    $activePromo = Promotion::create([
        'name' => 'Active Promotion',
        'code' => 'ACTIVE20',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 20,
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $inactivePromo = Promotion::create([
        'name' => 'Inactive Promotion',
        'code' => 'INACTIVE20',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 20,
        'is_active' => false,
    ]);

    $expiredPromo = Promotion::create([
        'name' => 'Expired Promotion',
        'code' => 'EXPIRED20',
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 20,
        'is_active' => true,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->subDays(2),
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('promotions', fn ($promos) => collect($promos)->contains('id', $activePromo->id)
            && ! collect($promos)->contains('id', $inactivePromo->id)
            && ! collect($promos)->contains('id', $expiredPromo->id)
        )
    );
});

test('home page displays featured products and excludes inactive ones', function () {
    $category = Category::create([
        'name' => 'Face Rituals',
        'slug' => 'face-rituals',
        'is_active' => true,
    ]);

    $featuredActive = Product::create([
        'category_id' => $category->id,
        'name' => 'Featured Botanical Serum',
        'slug' => 'featured-botanical-serum',
        'price' => 29.00,
        'is_active' => true,
        'is_featured' => true,
    ]);

    $featuredInactive = Product::create([
        'category_id' => $category->id,
        'name' => 'Featured Inactive Serum',
        'slug' => 'featured-inactive-serum',
        'price' => 35.00,
        'is_active' => false,
        'is_featured' => true,
    ]);

    $nonFeatured = Product::create([
        'category_id' => $category->id,
        'name' => 'Non Featured Product',
        'slug' => 'non-featured-product',
        'price' => 12.00,
        'is_active' => true,
        'is_featured' => false,
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('featuredProducts', fn ($products) => collect($products)->contains('id', $featuredActive->id)
            && ! collect($products)->contains('id', $featuredInactive->id)
            && ! collect($products)->contains('id', $nonFeatured->id)
        )
    );
});
