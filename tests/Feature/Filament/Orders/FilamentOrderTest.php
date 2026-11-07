<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('non-admin customer cannot access filament orders list or edit page', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create();

    $this->actingAs($customer, 'web')
        ->get('/admin/orders')
        ->assertRedirect('/admin/login');

    $this->actingAs($customer, 'web')
        ->get("/admin/orders/{$order->id}/edit")
        ->assertRedirect('/admin/login');
});

test('admin can view orders list and edit order page in filament', function () {
    $admin = AdminUser::factory()->superAdmin()->create();

    $payment = PaymentOption::factory()->stripe()->create();
    $order = Order::factory()->create([
        'payment_option_id' => $payment->id,
        'stripe_session_id' => 'cs_test_session_abc123',
        'stripe_payment_intent_id' => 'pi_test_intent_xyz789',
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $this->actingAs($admin, 'admin')
        ->get('/admin/orders')
        ->assertOk()
        ->assertSee($order->number);

    $this->actingAs($admin, 'admin')
        ->get("/admin/orders/{$order->id}/edit")
        ->assertOk()
        ->assertSee($order->number)
        ->assertSee('cs_test_session_abc123')
        ->assertSee('pi_test_intent_xyz789');
});

test('admin can mark order as shipped and delivered via filament action', function () {
    $admin = AdminUser::factory()->superAdmin()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(EditOrder::class, ['record' => $order->id])
        ->callAction('markShipped');

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);

    Livewire::actingAs($admin, 'admin')
        ->test(EditOrder::class, ['record' => $order->id])
        ->callAction('markDelivered');

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
});

test('admin can cancel order in filament and reserved stock is released to inventory', function () {
    $admin = AdminUser::factory()->superAdmin()->create();

    $product = Product::factory()->create([
        'track_inventory' => true,
        'stock_quantity' => 5,
    ]);

    $order = Order::factory()->create([
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(EditOrder::class, ['record' => $order->id])
        ->callAction('cancelOrder');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
    expect($product->fresh()->stock_quantity)->toBe(7);
});

test('admin can issue full refund in filament and inventory is restocked', function () {
    $admin = AdminUser::factory()->superAdmin()->create();

    $product = Product::factory()->create([
        'track_inventory' => true,
        'stock_quantity' => 10,
    ]);

    $paymentOption = PaymentOption::factory()->bankTransfer()->create();

    $order = Order::factory()->create([
        'payment_option_id' => $paymentOption->id,
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'total' => 60.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(EditOrder::class, ['record' => $order->id])
        ->callAction('refund', [
            'amount' => 60.00,
            'reason' => 'requested_by_customer',
            'restore_inventory' => true,
        ]);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Refunded);
    expect($order->status)->toBe(OrderStatus::Refunded);
    expect($product->fresh()->stock_quantity)->toBe(13);
});

test('admin can issue partial refund in filament without changing order status to refunded', function () {
    $admin = AdminUser::factory()->superAdmin()->create();

    $paymentOption = PaymentOption::factory()->bankTransfer()->create();

    $order = Order::factory()->create([
        'payment_option_id' => $paymentOption->id,
        'status' => OrderStatus::Shipped,
        'payment_status' => PaymentStatus::Paid,
        'total' => 100.00,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(EditOrder::class, ['record' => $order->id])
        ->callAction('refund', [
            'amount' => 30.00,
            'reason' => 'requested_by_customer',
            'restore_inventory' => false,
        ]);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded);
    expect($order->status)->toBe(OrderStatus::Shipped);
});

test('refund action is not visible on unpaid orders', function () {
    $admin = AdminUser::factory()->superAdmin()->create();

    $order = Order::factory()->create([
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(EditOrder::class, ['record' => $order->id])
        ->assertActionHidden('refund');
});
