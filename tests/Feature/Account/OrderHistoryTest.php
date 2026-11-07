<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('Account Order History', function () {
    describe('index', function () {
        it('redirects unauthenticated guests to login', function () {
            $response = $this->get(route('account.orders.index'));

            $response->assertRedirect(route('login'));
        });

        it('renders orders list for authenticated customer with pagination', function () {
            $user = User::factory()->create();
            Order::factory()->count(12)->forUser($user)->withItems(1)->create();

            $response = actingAsCustomer($user)->get(route('account.orders.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Account/Orders')
                ->has('orders.data', 10)
                ->where('orders.total', 12)
            );
        });

        it('only displays orders belonging to the authenticated customer', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            $orderA = Order::factory()->forUser($userA)->withItems(1)->create();
            Order::factory()->forUser($userB)->withItems(1)->create();

            $response = actingAsCustomer($userA)->get(route('account.orders.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Account/Orders')
                ->has('orders.data', 1)
                ->where('orders.data.0.public_id', $orderA->public_id)
            );
        });
    });

    describe('show', function () {
        it('redirects unauthenticated guests to login', function () {
            $order = Order::factory()->create();

            $response = $this->get(route('account.orders.show', ['order' => $order->public_id]));

            $response->assertRedirect(route('login'));
        });

        it('renders order details page with full items, delivery, and payment relations', function () {
            $user = User::factory()->create();
            $product = Product::factory()->create(['name' => 'Rose Damascena Toner']);
            $delivery = DeliveryOption::factory()->standard()->create(['name' => 'Botanical Courier']);
            $paymentOption = PaymentOption::factory()->stripe()->create(['name' => 'Credit Card']);

            $order = Order::factory()->forUser($user)->create([
                'delivery_option_id' => $delivery->id,
                'payment_option_id' => $paymentOption->id,
                'status' => OrderStatus::Processing,
                'payment_status' => PaymentStatus::Paid,
            ]);

            OrderItem::factory()->forProduct($product, 2)->create([
                'order_id' => $order->id,
                'product_name' => 'Rose Damascena Toner',
                'unit_price' => 30.00,
                'subtotal' => 60.00,
            ]);

            $response = actingAsCustomer($user)->get(route('account.orders.show', ['order' => $order->public_id]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Account/Order')
                ->where('order.public_id', $order->public_id)
                ->where('order.status', 'processing')
                ->where('order.payment_status', 'paid')
                ->has('order.items', 1)
                ->where('order.items.0.product_name', 'Rose Damascena Toner')
                ->where('order.deliveryOption.name', 'Botanical Courier')
                ->where('order.paymentOption.name', 'Credit Card')
            );
        });

        it('forbids authenticated customer from accessing another customers order detail', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            $orderA = Order::factory()->forUser($userA)->withItems(1)->create();

            $response = actingAsCustomer($userB)->get(route('account.orders.show', ['order' => $orderA->public_id]));

            $response->assertForbidden();
        });

        it('returns 404 when order public_id does not exist', function () {
            $user = User::factory()->create();

            $response = actingAsCustomer($user)->get(route('account.orders.show', ['order' => 'non_existent_ulid_12345']));

            $response->assertNotFound();
        });
    });
});
