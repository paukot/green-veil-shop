<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentOption;
use App\Models\StripeEvent;

test('payment option factory can create active payment options', function () {
    $option = PaymentOption::factory()->stripe()->create();

    expect($option)->toBeInstanceOf(PaymentOption::class);
    expect($option->code)->toBe('stripe');
    expect($option->driver)->toBe('stripe');
    expect($option->is_active)->toBeTrue();
});

test('order factory creates orders with valid ULID public_id and number', function () {
    $paymentOption = PaymentOption::factory()->stripe()->create();

    $order = Order::factory()->create([
        'payment_option_id' => $paymentOption->id,
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    expect($order->public_id)->not->toBeEmpty();
    expect(strlen($order->public_id))->toBe(26);
    expect($order->number)->toStartWith('GV-');
    expect($order->paymentOption->id)->toBe($paymentOption->id);
    expect($order->status)->toBe(OrderStatus::PendingPayment);
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('order items and payments relations are accessible from order', function () {
    $order = Order::factory()->create();

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Rose Damascena Facial Mist',
        'unit_price' => 32.00,
        'quantity' => 2,
        'subtotal' => 64.00,
    ]);

    $payment = Payment::factory()->paid()->create([
        'order_id' => $order->id,
        'amount' => 64.00,
        'currency' => 'EUR',
    ]);

    expect($order->items)->toHaveCount(1);
    expect($order->items->first()->product_name)->toBe('Rose Damascena Facial Mist');
    expect($order->payments)->toHaveCount(1);
    expect($order->payments->first()->status)->toBe(PaymentStatus::Paid);
});

test('stripe event model can store and query webhook events idempotently', function () {
    $event = StripeEvent::create([
        'stripe_event_id' => 'evt_test_123456789',
        'type' => 'checkout.session.completed',
        'payload' => ['id' => 'evt_test_123456789', 'type' => 'checkout.session.completed'],
        'processed_at' => now(),
    ]);

    expect($event->exists)->toBeTrue();
    expect(StripeEvent::where('stripe_event_id', 'evt_test_123456789')->exists())->toBeTrue();
});
