<?php

use App\Actions\Orders\RefundOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentOption;

it('records partial refunds against the remaining balance', function () {
    $order = Order::factory()->paid()->create([
        'payment_option_id' => PaymentOption::factory()->bankTransfer()->create()->id,
        'total' => 100.00,
    ]);

    $result = app(RefundOrder::class)->execute($order, 30.00);

    expect($result->successful)->toBeTrue();
    $order->refresh();
    expect((float) $order->refunded_amount)->toBe(30.0);
    expect($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded);
    expect($order->refundableAmount())->toBe(70.0);
});

it('marks the order refunded once partial refunds cover the total', function () {
    $order = Order::factory()->paid()->create([
        'payment_option_id' => PaymentOption::factory()->bankTransfer()->create()->id,
        'total' => 100.00,
        'refunded_amount' => 60.00,
    ]);

    app(RefundOrder::class)->execute($order, 40.00);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Refunded);
    expect($order->status)->toBe(OrderStatus::Refunded);
});

it('rejects a refund larger than the remaining balance', function () {
    $order = Order::factory()->paid()->create([
        'payment_option_id' => PaymentOption::factory()->bankTransfer()->create()->id,
        'total' => 100.00,
        'refunded_amount' => 90.00,
    ]);

    $result = app(RefundOrder::class)->execute($order, 20.00);

    expect($result->successful)->toBeFalse();
    expect((float) $order->fresh()->refunded_amount)->toBe(90.0);
});
