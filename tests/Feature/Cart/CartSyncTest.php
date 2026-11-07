<?php

use App\Models\CartItem;
use App\Models\DeliveryOption;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    DeliveryOption::factory()->create([
        'name' => 'Standard Botanical Delivery',
        'code' => 'standard',
        'price' => 4.95,
        'free_threshold' => 50.00,
        'is_active' => true,
    ]);
});

test('guest can sync cart items and receive reconciled summary and delivery options', function () {
    $productA = Product::factory()->create(['price' => 20.00, 'stock_quantity' => 10, 'is_active' => true]);
    $productB = Product::factory()->create(['price' => 35.00, 'stock_quantity' => 10, 'is_active' => true]);

    $response = $this->postJson(route('cart.sync'), [
        'items' => [
            ['product_id' => $productA->id, 'quantity' => 1],
            ['product_id' => $productB->id, 'quantity' => 1],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('summary.count', 2)
        ->assertJsonPath('summary.subtotal', 55)
        ->assertJsonPath('summary.qualifies_for_free_shipping', true)
        ->assertJsonCount(2, 'items');

    // Guest session contains the synced items
    $sessionCart = session('cart');
    expect($sessionCart)->toHaveKey($productA->id)
        ->and($sessionCart)->toHaveKey($productB->id);
});

test('authenticated user syncs cart items directly to database table', function () {
    $user = User::factory()->create();
    $productA = Product::factory()->create(['price' => 15.00, 'stock_quantity' => 10, 'is_active' => true]);
    $productB = Product::factory()->create(['price' => 25.00, 'stock_quantity' => 5, 'is_active' => true]);

    // Pre-existing item that should be removed if omitted
    CartItem::create(['user_id' => $user->id, 'product_id' => Product::factory()->create()->id, 'quantity' => 1]);

    $response = $this->actingAs($user)->postJson(route('cart.sync'), [
        'items' => [
            ['product_id' => $productA->id, 'quantity' => 2],
            ['product_id' => $productB->id, 'quantity' => 1],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('summary.count', 3)
        ->assertJsonPath('summary.subtotal', 55);

    $this->assertDatabaseHas('cart_items', [
        'user_id' => $user->id,
        'product_id' => $productA->id,
        'quantity' => 2,
    ]);
    $this->assertDatabaseHas('cart_items', [
        'user_id' => $user->id,
        'product_id' => $productB->id,
        'quantity' => 1,
    ]);
});

test('sync caps quantity when stock is limited and returns warnings', function () {
    $product = Product::factory()->create([
        'name' => 'Rare Orchid Mist',
        'stock_quantity' => 3,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $response = $this->postJson(route('cart.sync'), [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('summary.count', 3)
        ->assertJsonPath('items.0.quantity', 3);

    $warnings = $response->json('warnings');
    expect($warnings)->toHaveCount(1)
        ->and($warnings[0])->toContain('Rare Orchid Mist quantity was adjusted to 3 available');
});

test('sync removes out of stock and inactive products', function () {
    $inStock = Product::factory()->create(['price' => 20.00, 'stock_quantity' => 5, 'track_inventory' => true, 'is_active' => true]);
    $outOfStock = Product::factory()->create(['price' => 30.00, 'stock_quantity' => 0, 'track_inventory' => true, 'is_active' => true]);
    $inactive = Product::factory()->create(['price' => 40.00, 'is_active' => false]);

    $response = $this->postJson(route('cart.sync'), [
        'items' => [
            ['product_id' => $inStock->id, 'quantity' => 1],
            ['product_id' => $outOfStock->id, 'quantity' => 1],
            ['product_id' => $inactive->id, 'quantity' => 1],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('summary.count', 1)
        ->assertJsonCount(1, 'items');

    expect($response->json('warnings'))->toHaveCount(2);
});
