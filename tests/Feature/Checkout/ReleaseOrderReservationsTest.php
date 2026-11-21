<?php

use App\Actions\Checkout\ReleaseOrderReservations;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;

it('returns reserved stock and promotion usage', function () {
    $product = Product::factory()->create(['stock_quantity' => 3, 'track_inventory' => true]);
    $promotion = Promotion::factory()->create(['times_used' => 4]);
    $order = Order::factory()->pendingPayment()->create(['promotion_id' => $promotion->id]);
    OrderItem::factory()->for($order)->forProduct($product, 2)->create();

    $released = app(ReleaseOrderReservations::class)->execute($order);

    expect($released)->toBeTrue();
    expect($product->fresh()->stock_quantity)->toBe(5);
    expect($promotion->fresh()->times_used)->toBe(3);
    expect($order->fresh()->reservations_released_at)->not->toBeNull();
});

it('does not release the same order twice', function () {
    $product = Product::factory()->create(['stock_quantity' => 3, 'track_inventory' => true]);
    $promotion = Promotion::factory()->create(['times_used' => 4]);
    $order = Order::factory()->pendingPayment()->create(['promotion_id' => $promotion->id]);
    OrderItem::factory()->for($order)->forProduct($product, 2)->create();
    app(ReleaseOrderReservations::class)->execute($order);

    $releasedAgain = app(ReleaseOrderReservations::class)->execute($order);

    expect($releasedAgain)->toBeFalse();
    expect($product->fresh()->stock_quantity)->toBe(5);
    expect($promotion->fresh()->times_used)->toBe(3);
});

it('leaves stock of products that do not track inventory unchanged', function () {
    $product = Product::factory()->create(['stock_quantity' => 3, 'track_inventory' => false]);
    $order = Order::factory()->pendingPayment()->create();
    OrderItem::factory()->for($order)->forProduct($product, 2)->create();

    app(ReleaseOrderReservations::class)->execute($order);

    expect($product->fresh()->stock_quantity)->toBe(3);
});
