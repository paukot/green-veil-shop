<?php

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest can add an active product to the cart session', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Botanical Essence',
        'price' => 30.00,
        'stock_quantity' => 10,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $response = $this->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    $response->assertSessionHas("cart.{$product->id}.quantity", 2);

    $currentCart = session('cart');

    // Adding again with the current session increments quantity
    $responseSecond = $this->withSession(['cart' => $currentCart])->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $responseSecond->assertSessionHasNoErrors();
    $responseSecond->assertSessionHas("cart.{$product->id}.quantity", 5);
});

test('authenticated user can add a product to their database persisted cart', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'price' => 25.00,
        'stock_quantity' => 10,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('cart_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    // Adding again as authenticated user increments database record
    $responseSecond = $this->actingAs($user)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $responseSecond->assertSessionHasNoErrors();
    $this->assertDatabaseHas('cart_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);
});

test('cannot add inactive or out of stock product to cart', function () {
    $inactive = Product::factory()->create([
        'name' => 'Inactive Elixir',
        'is_active' => false,
    ]);

    $outOfStock = Product::factory()->create([
        'name' => 'Depleted Cream',
        'stock_quantity' => 0,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    // Inactive
    $responseInactive = $this->post(route('cart.items.store'), [
        'product_id' => $inactive->id,
        'quantity' => 1,
    ]);
    $responseInactive->assertSessionHasErrors('product_id');

    // Out of stock
    $responseOut = $this->post(route('cart.items.store'), [
        'product_id' => $outOfStock->id,
        'quantity' => 1,
    ]);
    $responseOut->assertSessionHasErrors('product_id');
});

test('cannot add more quantity than available stock', function () {
    $product = Product::factory()->create([
        'stock_quantity' => 3,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $response = $this->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $response->assertSessionHasErrors('quantity');
});

test('authenticated user can update and delete cart item', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'stock_quantity' => 10,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $item = CartItem::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    // Update quantity
    $response = $this->actingAs($user)->patch(route('cart.items.update', $item), [
        'quantity' => 4,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertEquals(4, $item->fresh()->quantity);

    // Update beyond stock limits
    $responseExcess = $this->actingAs($user)->patch(route('cart.items.update', $item), [
        'quantity' => 15,
    ]);

    $responseExcess->assertSessionHasErrors('quantity');
    $this->assertEquals(4, $item->fresh()->quantity);

    // Remove item
    $responseDel = $this->actingAs($user)->delete(route('cart.items.destroy', $item));
    $responseDel->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
});

test('guest can update and delete session cart item', function () {
    $product = Product::factory()->create([
        'stock_quantity' => 10,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $session = [
        'cart' => [
            $product->id => [
                'id' => $product->id,
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ],
    ];

    // Update session cart
    $response = $this->withSession($session)->patch(route('cart.items.update', $product->id), [
        'quantity' => 6,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas("cart.{$product->id}.quantity", 6);

    $currentCart = session('cart');

    // Delete session cart
    $responseDel = $this->withSession(['cart' => $currentCart])->delete(route('cart.items.destroy', $product->id));
    $responseDel->assertSessionHasNoErrors();
    $responseDel->assertSessionMissing("cart.{$product->id}");
});

test('user cannot update or delete another users cart item', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $product = Product::factory()->create(['is_active' => true]);

    $item = CartItem::create([
        'user_id' => $user1->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    // User2 tries to update User1's item
    $response = $this->actingAs($user2)->patch(route('cart.items.update', $item), [
        'quantity' => 2,
    ]);

    $response->assertForbidden();

    // User2 tries to delete User1's item
    $responseDel = $this->actingAs($user2)->delete(route('cart.items.destroy', $item));
    $responseDel->assertForbidden();
});

test('guest cart items merge into user database cart on login', function () {
    $user = User::factory()->create();
    $productA = Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);
    $productB = Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);

    // User already has product A (qty 3) in database
    CartItem::create([
        'user_id' => $user->id,
        'product_id' => $productA->id,
        'quantity' => 3,
    ]);

    $guestCart = [
        $productA->id => ['product_id' => $productA->id, 'quantity' => 2],
        $productB->id => ['product_id' => $productB->id, 'quantity' => 1],
    ];

    $cartService = app(CartService::class);
    $cartService->mergeGuestCart($guestCart, $user);

    // Product A merged quantity should be 5
    $this->assertDatabaseHas('cart_items', [
        'user_id' => $user->id,
        'product_id' => $productA->id,
        'quantity' => 5,
    ]);

    // Product B added for user
    $this->assertDatabaseHas('cart_items', [
        'user_id' => $user->id,
        'product_id' => $productB->id,
        'quantity' => 1,
    ]);
});
