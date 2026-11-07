<?php

use App\Actions\Checkout\CheckoutData;
use App\Actions\Checkout\PlaceOrder;
use App\Actions\Checkout\ReleaseOrderStock;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

function makeCheckoutData(DeliveryOption $delivery, PaymentOption $payment, ?User $user = null): CheckoutData
{
    return new CheckoutData(
        customerName: 'Margot Botanical',
        customerEmail: 'margot@greenveil.test',
        customerPhone: '+353 1 234 5678',
        deliveryOptionId: $delivery->id,
        paymentOptionId: $payment->id,
        shippingAddress: [
            'line1' => '44 Herbarium Way',
            'city' => 'Dublin',
            'postal_code' => 'D04 K382',
            'country' => 'IE',
        ],
        billingAddress: [
            'line1' => '44 Herbarium Way',
            'city' => 'Dublin',
            'postal_code' => 'D04 K382',
            'country' => 'IE',
        ],
        notes: 'Please leave at front porch.',
        user: $user,
    );
}

describe('PlaceOrder Action & Schema', function () {
    it('creates orders with valid ULID public_id, GV number, and relations', function () {
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

    it('creates order with snapshots and decrements stock', function () {
        $delivery = DeliveryOption::factory()->create(['price' => 5.00, 'free_threshold' => 100.00]);
        $payment = PaymentOption::factory()->stripe()->create();
        $product = Product::factory()->create([
            'price' => 30.00,
            'stock_quantity' => 10,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $request = Request::create('/checkout', 'POST');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('cart', [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 2],
        ]);

        $action = app(PlaceOrder::class);
        $order = $action->execute($request, makeCheckoutData($delivery, $payment));

        expect($order->status)->toBe(OrderStatus::PendingPayment);
        expect($order->payment_status)->toBe(PaymentStatus::Unpaid);
        expect((float) $order->subtotal)->toBe(60.00);
        expect((float) $order->delivery_amount)->toBe(5.00);
        expect((float) $order->total)->toBe(65.00);
        expect($order->items)->toHaveCount(1);
        expect($order->items->first()->product_name)->toBe($product->name);
        expect((float) $order->items->first()->unit_price)->toBe(30.00);

        expect($product->fresh()->stock_quantity)->toBe(8);
    });

    it('throws validation exception when cart is empty', function () {
        $delivery = DeliveryOption::factory()->create();
        $payment = PaymentOption::factory()->stripe()->create();

        $request = Request::create('/checkout', 'POST');
        $request->setLaravelSession(app('session.store'));
        $request->session()->forget('cart');

        $action = app(PlaceOrder::class);

        expect(fn () => $action->execute($request, makeCheckoutData($delivery, $payment)))
            ->toThrow(ValidationException::class);
    });

    it('throws validation exception when a cart product is inactive', function () {
        $delivery = DeliveryOption::factory()->create();
        $payment = PaymentOption::factory()->stripe()->create();
        $product = Product::factory()->create([
            'is_active' => false,
        ]);

        $request = Request::create('/checkout', 'POST');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('cart', [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ]);

        $action = app(PlaceOrder::class);

        expect(fn () => $action->execute($request, makeCheckoutData($delivery, $payment)))
            ->toThrow(ValidationException::class);
    });

    it('throws validation exception when insufficient stock is available', function () {
        $delivery = DeliveryOption::factory()->create();
        $payment = PaymentOption::factory()->stripe()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 2,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $request = Request::create('/checkout', 'POST');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('cart', [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 5],
        ]);

        $action = app(PlaceOrder::class);

        expect(fn () => $action->execute($request, makeCheckoutData($delivery, $payment)))
            ->toThrow(ValidationException::class);

        expect($product->fresh()->stock_quantity)->toBe(2);
    });

    it('applies active promotion and ignores expired promotion', function () {
        $delivery = DeliveryOption::factory()->create(['price' => 0.00]);
        $payment = PaymentOption::factory()->stripe()->create();
        $product = Product::factory()->create([
            'price' => 100.00,
            'is_active' => true,
            'track_inventory' => false,
        ]);

        Promotion::factory()->create([
            'applies_to_all_products' => true,
            'is_active' => true,
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 50,
            'ends_at' => now()->subDay(),
        ]);

        $activePromo = Promotion::factory()->create([
            'applies_to_all_products' => true,
            'is_active' => true,
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $request = Request::create('/checkout', 'POST');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('cart', [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ]);

        $action = app(PlaceOrder::class);
        $order = $action->execute($request, makeCheckoutData($delivery, $payment));

        expect((float) $order->subtotal)->toBe(100.00);
        expect((float) $order->discount_amount)->toBe(10.00);
        expect((float) $order->total)->toBe(90.00);
        expect($order->promotion_id)->toBe($activePromo->id);
    });

    it('release order stock returns reserved stock back to inventory', function () {
        $product = Product::factory()->create([
            'stock_quantity' => 5,
            'track_inventory' => true,
        ]);

        $delivery = DeliveryOption::factory()->create(['price' => 0.00]);
        $payment = PaymentOption::factory()->stripe()->create();

        $request = Request::create('/checkout', 'POST');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('cart', [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 2],
        ]);

        $order = app(PlaceOrder::class)->execute($request, makeCheckoutData($delivery, $payment));
        expect($product->fresh()->stock_quantity)->toBe(3);

        app(ReleaseOrderStock::class)->execute($order);
        expect($product->fresh()->stock_quantity)->toBe(5);
    });
});
