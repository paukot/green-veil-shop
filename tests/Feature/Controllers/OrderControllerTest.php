<?php

use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('OrderController', function () {
    describe('index', function () {
        it('redirects unauthenticated users to login', function () {
            $response = $this->get(route('account.orders.index'));

            $response->assertRedirect(route('login'));
        });

        it('renders orders list for authenticated user with pagination', function () {
            $user = User::factory()->create();
            Order::factory()->count(12)->forUser($user)->withItems(1)->create();

            $response = $this->actingAs($user)->get(route('account.orders.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Account/Orders')
                ->has('orders.data', 10)
                ->where('orders.total', 12)
            );
        });

        it('only displays orders belonging to the authenticated user', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            $orderA = Order::factory()->forUser($userA)->withItems(1)->create();
            $orderB = Order::factory()->forUser($userB)->withItems(1)->create();

            $response = $this->actingAs($userA)->get(route('account.orders.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Account/Orders')
                ->has('orders.data', 1)
                ->where('orders.data.0.public_id', $orderA->public_id)
            );
        });
    });

    describe('show', function () {
        it('redirects unauthenticated users to login', function () {
            $order = Order::factory()->create();

            $response = $this->get(route('account.orders.show', ['order' => $order->public_id]));

            $response->assertRedirect(route('login'));
        });

        it('renders single order detail page with items, delivery, payment, and addresses', function () {
            $user = User::factory()->create();
            $delivery = DeliveryOption::factory()->standard()->create();
            $paymentOption = PaymentOption::factory()->stripe()->create();

            $order = Order::factory()->forUser($user)->create([
                'delivery_option_id' => $delivery->id,
                'payment_option_id' => $paymentOption->id,
            ]);

            $product = Product::factory()->price(35.00)->create(['name' => 'Botanical Essence']);
            OrderItem::factory()->forProduct($product, 2)->create(['order_id' => $order->id]);
            Payment::factory()->forOrder($order)->paid()->create();

            $response = $this->actingAs($user)->get(route('account.orders.show', ['order' => $order->public_id]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Account/Order')
                ->where('order.public_id', $order->public_id)
                ->has('items', 1)
                ->where('items.0.product_name', 'Botanical Essence')
                ->where('items.0.quantity', 2)
                ->where('deliveryOption.code', 'standard')
                ->where('paymentOption.code', 'stripe')
            );
        });

        it('forbids user from viewing another users order with 403 Forbidden', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            $orderA = Order::factory()->forUser($userA)->create();

            $response = $this->actingAs($userB)->get(route('account.orders.show', ['order' => $orderA->public_id]));

            $response->assertForbidden();
        });
    });
});
