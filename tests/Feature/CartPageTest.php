<?php

use App\Models\Category;
use App\Models\DeliveryOption;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('empty cart page renders empty bag state', function () {
    $response = $this->get(route('cart.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Cart/Index')
        ->has('items', 0)
        ->where('summary.count', 0)
        ->where('summary.subtotal', 0)
        ->has('deliveryOptions')
    );
});

test('cart page displays items, free delivery thresholds, and delivery options', function () {
    $category = Category::factory()->create(['name' => 'Botanicals']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Rose Water Toner',
        'price' => 20.00,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    DeliveryOption::create([
        'name' => 'Standard Shipping',
        'code' => 'standard',
        'price' => 4.99,
        'free_threshold' => 50.00,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $user = User::factory()->create();

    // Add 2 items to user cart
    $this->actingAs($user)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response = $this->actingAs($user)->get(route('cart.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Cart/Index')
        ->has('items', 1)
        ->where('items.0.product.name', 'Rose Water Toner')
        ->where('items.0.quantity', 2)
        ->where('items.0.subtotal', 40)
        ->where('summary.count', 2)
        ->where('summary.subtotal', 40)
        ->where('summary.free_shipping_threshold', 50)
        ->where('summary.amount_until_free_shipping', 10)
        ->where('summary.qualifies_for_free_shipping', false)
        ->has('deliveryOptions', 1)
        ->where('deliveryOptions.0.code', 'standard')
        ->where('deliveryOptions.0.is_free', false)
    );
});

test('cart qualifies for free shipping when exceeding threshold', function () {
    $product = Product::factory()->create([
        'name' => 'Luxury Botanical Oil',
        'price' => 60.00,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    DeliveryOption::create([
        'name' => 'Standard Shipping',
        'code' => 'standard',
        'price' => 4.99,
        'free_threshold' => 50.00,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)->get(route('cart.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('summary.subtotal', 60)
        ->where('summary.amount_until_free_shipping', 0)
        ->where('summary.qualifies_for_free_shipping', true)
        ->where('deliveryOptions.0.is_free', true)
    );
});
