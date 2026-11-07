<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\SliderItem;
use App\Models\User;

test('all factories create valid model instances without constraint errors', function () {
    $user = User::factory()->create();
    expect($user)->toBeInstanceOf(User::class);

    $category = Category::factory()->withChildren(2)->withProducts(2)->create();
    expect($category->children)->toHaveCount(2)
        ->and($category->products)->toHaveCount(2);

    $inactiveCat = Category::factory()->inactive()->create();
    expect($inactiveCat->is_active)->toBeFalse();

    $product = Product::factory()->featured()->inStock(15)->withImages(2)->create();
    expect($product->is_featured)->toBeTrue()
        ->and($product->stock_quantity)->toBe(15)
        ->and($product->images)->toHaveCount(2);

    $outOfStock = Product::factory()->outOfStock()->create();
    expect($outOfStock->stock_quantity)->toBe(0)
        ->and($outOfStock->isInStock())->toBeFalse();

    $order = Order::factory()->forUser($user)->paid()->withItems(3, 20.00)->create();
    expect($order->user_id)->toBe($user->id)
        ->and($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Processing)
        ->and($order->items)->toHaveCount(3)
        ->and((float) $order->subtotal)->toBe(60.00);

    $cartItem = CartItem::factory()->forUser($user)->forProduct($product, 3)->create();
    expect($cartItem->user_id)->toBe($user->id)
        ->and($cartItem->product_id)->toBe($product->id)
        ->and($cartItem->quantity)->toBe(3)
        ->and($cartItem->subtotal())->toBe(3 * (float) $product->price);

    $delivery = DeliveryOption::factory()->standard()->create();
    expect($delivery->code)->toBe('standard');

    $paymentOption = PaymentOption::factory()->stripe()->create();
    expect($paymentOption->code)->toBe('stripe');

    $payment = Payment::factory()->forOrder($order)->paid()->create();
    expect($payment->order_id)->toBe($order->id)
        ->and($payment->status)->toBe(PaymentStatus::Paid);

    $promotion = Promotion::factory()->percentage(20)->create();
    expect((float) $promotion->discount_value)->toBe(20.00);

    $slider = SliderItem::factory()->visible()->create();
    expect($slider->is_active)->toBeTrue();
});
