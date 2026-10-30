<?php

use App\Models\CartItem;
use App\Models\Favourite;
use App\Models\Product;
use App\Models\User;

test('guest cart items and favourites are merged into user account on login', function () {
    $productA = Product::factory()->create([
        'price' => 2500,
        'stock_quantity' => 10,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $productB = Product::factory()->create([
        'price' => 4500,
        'stock_quantity' => 5,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'email' => 'merge-customer@greenveil.test',
        'password' => bcrypt('password123'),
    ]);

    // Pre-populate guest session with cart item and favourite
    $this->withSession([
        'cart' => [
            $productA->id => [
                'id' => $productA->id,
                'product_id' => $productA->id,
                'quantity' => 2,
            ],
        ],
        'favourites' => [$productB->id],
    ]);

    $response = $this->post('/login', [
        'email' => 'merge-customer@greenveil.test',
        'password' => 'password123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home'));

    // Check database has the merged records
    expect(CartItem::query()->where('user_id', $user->id)->where('product_id', $productA->id)->value('quantity'))->toBe(2);
    expect(Favourite::query()->where('user_id', $user->id)->where('product_id', $productB->id)->exists())->toBeTrue();

    // Check session data is cleared
    expect(session()->has('cart'))->toBeFalse();
    expect(session()->has('favourites'))->toBeFalse();
});

test('existing user cart quantity is incremented when merging guest cart', function () {
    $product = Product::factory()->create([
        'stock_quantity' => 10,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'email' => 'merge-inc@greenveil.test',
        'password' => bcrypt('password123'),
    ]);

    // User already had 2 units in DB cart
    CartItem::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    // Guest has 3 units in session
    $this->withSession([
        'cart' => [
            $product->id => [
                'id' => $product->id,
                'product_id' => $product->id,
                'quantity' => 3,
            ],
        ],
    ]);

    $this->post('/login', [
        'email' => 'merge-inc@greenveil.test',
        'password' => 'password123',
    ]);

    // Total should be 2 + 3 = 5
    expect(CartItem::query()->where('user_id', $user->id)->where('product_id', $product->id)->value('quantity'))->toBe(5);
});

test('guest cart items and favourites are merged into user account on registration', function () {
    $productA = Product::factory()->create([
        'stock_quantity' => 8,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $productB = Product::factory()->create([
        'is_active' => true,
    ]);

    // Guest puts items into cart and favourites
    $this->withSession([
        'cart' => [
            $productA->id => [
                'id' => $productA->id,
                'product_id' => $productA->id,
                'quantity' => 3,
            ],
        ],
        'favourites' => [$productB->id],
    ]);

    $this->post('/register', [
        'name' => 'Camille Violet',
        'email' => 'camille@greenveil.test',
        'password' => 'apothecary123',
        'password_confirmation' => 'apothecary123',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'camille@greenveil.test')->first();
    expect($user)->not->toBeNull();

    expect(CartItem::query()->where('user_id', $user->id)->where('product_id', $productA->id)->value('quantity'))->toBe(3);
    expect(Favourite::query()->where('user_id', $user->id)->where('product_id', $productB->id)->exists())->toBeTrue();
});

test('inactive products are not merged into cart or favourites on login', function () {
    $inactiveProduct = Product::factory()->create([
        'is_active' => false,
    ]);

    $user = User::factory()->create([
        'email' => 'inactive-test@greenveil.test',
        'password' => bcrypt('password123'),
    ]);

    $this->withSession([
        'cart' => [
            $inactiveProduct->id => [
                'id' => $inactiveProduct->id,
                'product_id' => $inactiveProduct->id,
                'quantity' => 1,
            ],
        ],
        'favourites' => [$inactiveProduct->id],
    ]);

    $this->post('/login', [
        'email' => 'inactive-test@greenveil.test',
        'password' => 'password123',
    ]);

    expect(CartItem::query()->where('user_id', $user->id)->where('product_id', $inactiveProduct->id)->exists())->toBeFalse();
    expect(Favourite::query()->where('user_id', $user->id)->where('product_id', $inactiveProduct->id)->exists())->toBeFalse();
});
