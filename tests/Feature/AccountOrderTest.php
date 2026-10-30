<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentOption;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login when accessing account orders list', function () {
    $this->get('/account/orders')
        ->assertRedirect('/login');
});

test('guests are redirected to login when accessing account order detail', function () {
    $order = Order::factory()->create();

    $this->get("/account/orders/{$order->public_id}")
        ->assertRedirect('/login');
});

test('authenticated customer can view their own orders list and not others', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $delivery = DeliveryOption::factory()->create();
    $payment = PaymentOption::factory()->stripe()->create();

    // Orders belonging to this user
    $userOrder1 = Order::factory()->create([
        'user_id' => $user->id,
        'delivery_option_id' => $delivery->id,
        'payment_option_id' => $payment->id,
        'total' => 45.00,
        'status' => OrderStatus::Completed,
        'payment_status' => PaymentStatus::Paid,
        'placed_at' => now()->subDay(),
    ]);

    $userOrder2 = Order::factory()->create([
        'user_id' => $user->id,
        'delivery_option_id' => $delivery->id,
        'payment_option_id' => $payment->id,
        'total' => 80.00,
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'placed_at' => now(),
    ]);

    // Order belonging to someone else
    $otherOrder = Order::factory()->create([
        'user_id' => $otherUser->id,
        'delivery_option_id' => $delivery->id,
        'payment_option_id' => $payment->id,
        'total' => 120.00,
        'placed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/account/orders')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Account/Orders')
            ->has('orders.data', 2)
            ->where('orders.data.0.public_id', $userOrder2->public_id)
            ->where('orders.data.1.public_id', $userOrder1->public_id)
            ->where('orders.total', 2)
        );
});

test('authenticated customer can view their own order detail with relations', function () {
    $user = User::factory()->create();

    $delivery = DeliveryOption::factory()->create([
        'name' => 'Artisan Express Courier',
        'code' => 'express',
    ]);

    $payment = PaymentOption::factory()->bankTransfer()->create([
        'name' => 'Direct Botanical Transfer',
        'instructions' => 'Please transfer to IE12BOTANICAL.',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'delivery_option_id' => $delivery->id,
        'payment_option_id' => $payment->id,
        'subtotal' => 70.00,
        'delivery_amount' => 5.00,
        'discount_amount' => 10.00,
        'tax_amount' => 12.00,
        'total' => 65.00,
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'shipping_address' => [
            'line1' => '42 Wildflower Way',
            'city' => 'Galway',
            'postal_code' => 'H91 X4V2',
            'country' => 'IE',
        ],
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Rose Water Toner',
        'product_sku' => 'GV-ROSE-200',
        'unit_price' => 35.00,
        'quantity' => 2,
        'subtotal' => 70.00,
    ]);

    $this->actingAs($user)
        ->get("/account/orders/{$order->public_id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Account/Order')
            ->where('order.public_id', $order->public_id)
            ->where('order.number', $order->number)
            ->where('order.total', 65)
            ->where('order.status', 'pending_payment')
            ->where('order.payment_status', 'unpaid')
            ->has('items', 1)
            ->where('items.0.product_name', 'Rose Water Toner')
            ->where('deliveryOption.name', 'Artisan Express Courier')
            ->where('paymentOption.instructions', 'Please transfer to IE12BOTANICAL.')
        );
});

test('authenticated customer cannot view another customers order and receives 403', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->get("/account/orders/{$order->public_id}")
        ->assertForbidden();
});

test('requesting nonexistent order returns 404', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/account/orders/nonexistent-ulid-9999')
        ->assertNotFound();
});
