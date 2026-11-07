<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentOption;
use App\Models\Product;

test('sweep stale orders cancels unpaid orders older than threshold and releases stock', function () {
    $product = Product::factory()->create([
        'stock_quantity' => 10,
        'track_inventory' => true,
    ]);

    $stripePaymentOption = PaymentOption::factory()->stripe()->create();

    // Stale order placed 90 minutes ago
    $staleOrder = Order::factory()->create([
        'payment_option_id' => $stripePaymentOption->id,
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'placed_at' => now()->subMinutes(90),
    ]);

    OrderItem::factory()->create([
        'order_id' => $staleOrder->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    Payment::factory()->create([
        'order_id' => $staleOrder->id,
        'driver' => 'stripe',
        'status' => PaymentStatus::Unpaid,
    ]);

    // Fresh order placed 10 minutes ago
    $freshOrder = Order::factory()->create([
        'payment_option_id' => $stripePaymentOption->id,
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'placed_at' => now()->subMinutes(10),
    ]);

    OrderItem::factory()->create([
        'order_id' => $freshOrder->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    // Run the command
    $this->artisan('orders:sweep-stale', ['--minutes' => 60])
        ->expectsOutput('Successfully swept 1 stale order(s) and released inventory.')
        ->assertSuccessful();

    // Verify stale order cancelled
    $staleOrder->refresh();
    expect($staleOrder->status)->toBe(OrderStatus::Cancelled);
    expect($staleOrder->payment_status)->toBe(PaymentStatus::Expired);

    $payment = Payment::where('order_id', $staleOrder->id)->first();
    expect($payment->status)->toBe(PaymentStatus::Expired);

    // Verify fresh order unaffected
    $freshOrder->refresh();
    expect($freshOrder->status)->toBe(OrderStatus::PendingPayment);
    expect($freshOrder->payment_status)->toBe(PaymentStatus::Unpaid);

    // Stock for the 3 items released back: 10 + 3 = 13
    expect($product->fresh()->stock_quantity)->toBe(13);
});

test('sweep stale orders ignores orders already marked as paid', function () {
    $stripePaymentOption = PaymentOption::factory()->stripe()->create();

    $paidOrder = Order::factory()->create([
        'payment_option_id' => $stripePaymentOption->id,
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'placed_at' => now()->subHours(5),
    ]);

    $this->artisan('orders:sweep-stale')
        ->expectsOutput('No stale unpaid orders found older than the configured thresholds.')
        ->assertSuccessful();

    $paidOrder->refresh();
    expect($paidOrder->status)->toBe(OrderStatus::Processing);
    expect($paidOrder->payment_status)->toBe(PaymentStatus::Paid);
});

test('sweep stale orders cancels bank transfer orders older than bank-days threshold', function () {
    $product = Product::factory()->create([
        'stock_quantity' => 5,
        'track_inventory' => true,
    ]);

    $bankPaymentOption = PaymentOption::factory()->bankTransfer()->create();

    $staleBankOrder = Order::factory()->create([
        'payment_option_id' => $bankPaymentOption->id,
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'placed_at' => now()->subDays(10),
    ]);

    OrderItem::factory()->create([
        'order_id' => $staleBankOrder->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->artisan('orders:sweep-stale', ['--bank-days' => 7])
        ->expectsOutput('Successfully swept 1 stale order(s) and released inventory.')
        ->assertSuccessful();

    $staleBankOrder->refresh();
    expect($staleBankOrder->status)->toBe(OrderStatus::Cancelled);
    expect($staleBankOrder->payment_status)->toBe(PaymentStatus::Expired);
    expect($product->fresh()->stock_quantity)->toBe(7);
});
