<?php

use App\Models\CartItem;
use App\Models\DeliveryOption;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('CartController', function () {
    describe('index', function () {
        it('renders empty cart page with default thresholds and delivery options', function () {
            DeliveryOption::factory()->standard()->create([
                'price' => 4.99,
                'free_threshold' => 50.00,
            ]);

            $response = $this->get(route('cart.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Cart/Index')
                ->has('items', 0)
                ->where('summary.count', 0)
                ->where('summary.subtotal', 0)
                ->where('summary.free_shipping_threshold', 50)
                ->where('summary.amount_until_free_shipping', 50)
                ->where('summary.qualifies_for_free_shipping', false)
                ->has('deliveryOptions', 1)
            );
        });

        it('renders guest cart from session with computed free shipping threshold and delivery options', function () {
            $product = Product::factory()->price(20.00)->create(['name' => 'Rose Face Mist']);
            DeliveryOption::factory()->standard()->create([
                'price' => 4.99,
                'free_threshold' => 50.00,
            ]);

            $sessionCart = [
                'cart' => [
                    $product->id => [
                        'id' => $product->id,
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ];

            $response = $this->withSession($sessionCart)->get(route('cart.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Cart/Index')
                ->has('items', 1)
                ->where('items.0.id', $product->id)
                ->where('items.0.product_id', $product->id)
                ->where('items.0.quantity', 2)
                ->where('items.0.subtotal', 40)
                ->where('summary.count', 2)
                ->where('summary.subtotal', 40)
                ->where('summary.amount_until_free_shipping', 10)
                ->where('summary.qualifies_for_free_shipping', false)
            );
        });

        it('renders authenticated user cart from database records', function () {
            $user = User::factory()->create();
            $product = Product::factory()->price(30.00)->create(['name' => 'Botanical Night Oil']);
            CartItem::factory()->forUser($user)->forProduct($product, 2)->create();

            $response = $this->actingAs($user)->get(route('cart.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Cart/Index')
                ->has('items', 1)
                ->where('items.0.product.name', 'Botanical Night Oil')
                ->where('items.0.quantity', 2)
                ->where('items.0.subtotal', 60)
                ->where('summary.count', 2)
                ->where('summary.subtotal', 60)
            );
        });

        it('identifies free shipping qualification when subtotal meets threshold', function () {
            $product = Product::factory()->price(65.00)->create();
            DeliveryOption::factory()->standard()->create([
                'price' => 4.99,
                'free_threshold' => 50.00,
            ]);

            $sessionCart = [
                'cart' => [
                    $product->id => [
                        'id' => $product->id,
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ];

            $response = $this->withSession($sessionCart)->get(route('cart.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Cart/Index')
                ->where('summary.subtotal', 65)
                ->where('summary.amount_until_free_shipping', 0)
                ->where('summary.qualifies_for_free_shipping', true)
                ->where('deliveryOptions.0.is_free', true)
            );
        });
    });
});
