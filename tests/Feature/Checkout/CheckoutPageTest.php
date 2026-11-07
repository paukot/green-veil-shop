<?php

use App\Models\CartItem;
use App\Models\DeliveryOption;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('Checkout Page', function () {
    it('redirects to cart if shopping bag is empty', function () {
        $response = $this->get(route('checkout.show'));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('info');
    });

    it('renders checkout page with items, delivery options, and payment options for guest', function () {
        $product = Product::factory()->price(40.00)->create(['name' => 'Rose Serum']);
        $delivery = DeliveryOption::factory()->standard()->create();
        $payment = PaymentOption::factory()->bankTransfer()->create();

        $response = $this->withSession([
            'cart' => [
                $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
            ],
        ])->get(route('checkout.show'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Index')
            ->has('items', 1)
            ->where('items.0.product.name', 'Rose Serum')
            ->has('deliveryOptions')
            ->has('paymentOptions')
            ->where('customer', null)
        );
    });

    it('prefills customer info and addresses when user is authenticated', function () {
        $user = User::factory()->create([
            'name' => 'Eleanor Vance',
            'email' => 'eleanor@example.com',
        ]);

        $product = Product::factory()->create();
        CartItem::factory()->forUser($user)->forProduct($product, 1)->create();
        DeliveryOption::factory()->standard()->create();
        PaymentOption::factory()->stripe()->create();

        $response = actingAsCustomer($user)->get(route('checkout.show'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Index')
            ->where('customer.name', 'Eleanor Vance')
            ->where('customer.email', 'eleanor@example.com')
        );
    });
});
