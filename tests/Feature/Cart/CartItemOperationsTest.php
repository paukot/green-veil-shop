<?php

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;

describe('Cart Item Operations', function () {
    describe('store', function () {
        it('validates product_id and quantity on store', function () {
            $response = $this->post(route('cart.items.store'), []);
            $response->assertSessionHasErrors(['product_id']);

            $responseNonExistent = $this->post(route('cart.items.store'), [
                'product_id' => 999999,
                'quantity' => 1,
            ]);
            $responseNonExistent->assertSessionHasErrors(['product_id']);

            $product = Product::factory()->create();
            $responseZero = $this->post(route('cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 0,
            ]);
            $responseZero->assertSessionHasErrors(['quantity']);
        });

        it('rejects adding inactive or out-of-stock products', function () {
            $inactive = Product::factory()->inactive()->create();
            $outOfStock = Product::factory()->outOfStock()->create();

            $responseInactive = $this->post(route('cart.items.store'), [
                'product_id' => $inactive->id,
                'quantity' => 1,
            ]);
            $responseInactive->assertSessionHasErrors('product_id');

            $responseOut = $this->post(route('cart.items.store'), [
                'product_id' => $outOfStock->id,
                'quantity' => 1,
            ]);
            $responseOut->assertSessionHasErrors('product_id');
        });

        it('adds product to guest session cart and increments quantity on repeat addition', function () {
            $product = Product::factory()->inStock(10)->create();

            $response = $this->post(route('cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect();
            $response->assertSessionHas("cart.{$product->id}.quantity", 2);
            $response->assertSessionHas("cart.{$product->id}.id", $product->id);

            $currentCart = session('cart');

            $responseSecond = $this->withSession(['cart' => $currentCart])->post(route('cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 3,
            ]);

            $responseSecond->assertSessionHasNoErrors();
            $responseSecond->assertSessionHas("cart.{$product->id}.quantity", 5);
        });

        it('adds product to authenticated customer database cart and increments on repeat', function () {
            $user = User::factory()->create();
            $product = Product::factory()->inStock(10)->create();

            $response = actingAsCustomer($user)->post(route('cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

            $response->assertSessionHasNoErrors();
            $this->assertDatabaseHas('cart_items', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

            $responseSecond = actingAsCustomer($user)->post(route('cart.items.store'), [
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

        it('rejects adding quantity that exceeds available inventory', function () {
            $product = Product::factory()->inStock(3)->create();

            $response = $this->post(route('cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 5,
            ]);

            $response->assertSessionHasErrors('quantity');
        });
    });

    describe('update', function () {
        it('validates quantity on update', function () {
            $product = Product::factory()->create();

            $response = $this->patch(route('cart.items.update', $product->id), [
                'quantity' => 0,
            ]);

            $response->assertSessionHasErrors('quantity');
        });

        it('updates item quantity in guest session cart', function () {
            $product = Product::factory()->inStock(10)->create();
            $session = [
                'cart' => [
                    $product->id => [
                        'id' => $product->id,
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ];

            $response = $this->withSession($session)->patch(route('cart.items.update', $product->id), [
                'quantity' => 4,
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertSessionHas("cart.{$product->id}.quantity", 4);
        });

        it('returns 404 when guest updates item not in session cart', function () {
            $response = $this->patch(route('cart.items.update', 888888), [
                'quantity' => 2,
            ]);

            $response->assertNotFound();
        });

        it('updates item quantity for authenticated user in database', function () {
            $user = User::factory()->create();
            $product = Product::factory()->inStock(10)->create();
            $item = CartItem::factory()->forUser($user)->forProduct($product, 2)->create();

            $response = actingAsCustomer($user)->patch(route('cart.items.update', $item->id), [
                'quantity' => 5,
            ]);

            $response->assertSessionHasNoErrors();
            expect($item->fresh()->quantity)->toBe(5);
        });

        it('forbids authenticated user from updating another user cart item', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();
            $product = Product::factory()->create();
            $item = CartItem::factory()->forUser($userA)->forProduct($product, 1)->create();

            $response = actingAsCustomer($userB)->patch(route('cart.items.update', $item->id), [
                'quantity' => 3,
            ]);

            $response->assertForbidden();
            expect($item->fresh()->quantity)->toBe(1);
        });

        it('rejects updating quantity beyond available inventory stock', function () {
            $user = User::factory()->create();
            $product = Product::factory()->inStock(4)->create();
            $item = CartItem::factory()->forUser($user)->forProduct($product, 2)->create();

            $response = actingAsCustomer($user)->patch(route('cart.items.update', $item->id), [
                'quantity' => 10,
            ]);

            $response->assertSessionHasErrors('quantity');
            expect($item->fresh()->quantity)->toBe(2);
        });
    });

    describe('destroy', function () {
        it('removes item from guest session cart', function () {
            $product = Product::factory()->create();
            $session = [
                'cart' => [
                    $product->id => [
                        'id' => $product->id,
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ];

            $response = $this->withSession($session)->delete(route('cart.items.destroy', $product->id));

            $response->assertSessionHasNoErrors();
            $response->assertSessionMissing("cart.{$product->id}");
        });

        it('deletes cart item from database for authenticated user', function () {
            $user = User::factory()->create();
            $product = Product::factory()->create();
            $item = CartItem::factory()->forUser($user)->forProduct($product, 2)->create();

            $response = actingAsCustomer($user)->delete(route('cart.items.destroy', $item->id));

            $response->assertSessionHasNoErrors();
            $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
        });

        it('forbids authenticated user from deleting another user cart item', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();
            $product = Product::factory()->create();
            $item = CartItem::factory()->forUser($userA)->forProduct($product, 1)->create();

            $response = actingAsCustomer($userB)->delete(route('cart.items.destroy', $item->id));

            $response->assertForbidden();
            $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
        });
    });
});
