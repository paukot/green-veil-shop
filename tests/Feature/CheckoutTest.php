<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\User;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\Stripe\StripeGateway;
use Inertia\Testing\AssertableInertia as Assert;

test('empty cart redirects to cart index from checkout', function () {
    $response = $this->get(route('checkout.show'));

    $response->assertRedirect(route('cart.index'));
});

test('checkout page renders with cart items and options', function () {
    $product = Product::factory()->create([
        'name' => 'Botanical Essence Elixir',
        'price' => 45.00,
        'is_active' => true,
    ]);

    $delivery = DeliveryOption::factory()->create([
        'name' => 'Artisanal Courier',
        'price' => 5.00,
        'is_active' => true,
    ]);

    $payment = PaymentOption::factory()->bankTransfer()->create([
        'is_active' => true,
    ]);

    $response = $this->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ],
    ])->get(route('checkout.show'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Checkout/Index')
        ->has('items', 1)
        ->where('items.0.product.name', 'Botanical Essence Elixir')
        ->where('summary.subtotal', 45)
        ->has('deliveryOptions')
        ->has('paymentOptions')
    );
});

test('guest can complete order via bank transfer', function () {
    $product = Product::factory()->create([
        'price' => 40.00,
        'stock_quantity' => 5,
        'track_inventory' => true,
        'is_active' => true,
    ]);

    $delivery = DeliveryOption::factory()->create([
        'price' => 4.50,
        'free_threshold' => 100.00,
        'is_active' => true,
    ]);

    $payment = PaymentOption::factory()->bankTransfer()->create([
        'is_active' => true,
    ]);

    $response = $this->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 2],
        ],
    ])->post(route('checkout.store'), [
        'customer_name' => 'Lady Rosalind',
        'customer_email' => 'rosalind@botanicals.test',
        'customer_phone' => '+353 87 999 1111',
        'delivery_option_id' => $delivery->id,
        'payment_option_id' => $payment->id,
        'shipping_address' => [
            'line1' => '12 Rosewood Manor',
            'city' => 'Galway',
            'postal_code' => 'H91 E4X2',
            'country' => 'IE',
        ],
        'billing_same_as_shipping' => true,
        'notes' => 'Please ring the brass doorbell.',
    ]);

    $order = Order::query()->where('customer_email', 'rosalind@botanicals.test')->first();

    expect($order)->not->toBeNull();
    expect($order->status)->toBe(OrderStatus::PendingPayment);
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);
    expect((float) $order->subtotal)->toBe(80.00);
    expect((float) $order->delivery_amount)->toBe(4.50);
    expect((float) $order->total)->toBe(84.50);

    // Stock reserved
    expect($product->fresh()->stock_quantity)->toBe(3);

    // Payment record created
    $paymentRecord = Payment::where('order_id', $order->id)->first();
    expect($paymentRecord)->not->toBeNull();
    expect($paymentRecord->driver)->toBe('bank_transfer');
    expect($paymentRecord->status)->toBe(PaymentStatus::Unpaid);
    expect((float) $paymentRecord->amount)->toBe(84.50);

    // Cart cleared in session
    $this->assertEmpty(session('cart'));

    $response->assertRedirect(route('checkout.success', ['order' => $order->public_id]));
});

test('order with cash on delivery sets order status to processing', function () {
    $product = Product::factory()->create([
        'price' => 60.00,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    $delivery = DeliveryOption::factory()->create([
        'price' => 0.00,
        'free_threshold' => 50.00,
        'is_active' => true,
    ]);

    $payment = PaymentOption::factory()->cod()->create([
        'is_active' => true,
    ]);

    $response = $this->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ],
    ])->post(route('checkout.store'), [
        'customer_name' => 'Liam O\'Connor',
        'customer_email' => 'liam@greenveil.test',
        'delivery_option_id' => $delivery->id,
        'payment_option_id' => $payment->id,
        'shipping_address' => [
            'line1' => '88 Shannon Quay',
            'city' => 'Limerick',
            'postal_code' => 'V94 X728',
            'country' => 'IE',
        ],
        'billing_same_as_shipping' => true,
    ]);

    $order = Order::query()->where('customer_email', 'liam@greenveil.test')->first();

    expect($order)->not->toBeNull();
    expect($order->status)->toBe(OrderStatus::Processing);
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);

    $paymentRecord = Payment::where('order_id', $order->id)->first();
    expect($paymentRecord)->not->toBeNull();
    expect($paymentRecord->driver)->toBe('cod');

    $response->assertRedirect(route('checkout.success', ['order' => $order->public_id]));
});

test('checkout validates required fields', function () {
    $response = $this->post(route('checkout.store'), []);

    $response->assertSessionHasErrors([
        'customer_name',
        'customer_email',
        'delivery_option_id',
        'payment_option_id',
        'shipping_address',
    ]);
});

test('success page displays order details and bank instructions', function () {
    $delivery = DeliveryOption::factory()->create();
    $payment = PaymentOption::factory()->bankTransfer()->create([
        'instructions' => 'IBAN: IE89BOTANICAL0001234567',
    ]);

    $order = Order::factory()->create([
        'delivery_option_id' => $delivery->id,
        'payment_option_id' => $payment->id,
        'customer_name' => 'Fiona MacLeod',
        'total' => 55.00,
    ]);

    $response = $this->get(route('checkout.success', ['order' => $order->public_id]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Checkout/Success')
        ->where('order.public_id', $order->public_id)
        ->where('order.customer_name', 'Fiona MacLeod')
        ->where('paymentOption.instructions', 'IBAN: IE89BOTANICAL0001234567')
    );
});

test('cancel page displays order details and retry link', function () {
    $order = Order::factory()->create([
        'total' => 75.00,
    ]);

    $response = $this->get(route('checkout.cancel', ['order' => $order->public_id]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Checkout/Cancel')
        ->where('order.public_id', $order->public_id)
        ->where('order.number', $order->number)
    );
});

test('unauthorized customer cannot access another user order success page', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user1->id,
    ]);

    $response = $this->actingAs($user2)->get(route('checkout.success', ['order' => $order->public_id]));

    $response->assertForbidden();
});

test('checkout with stripe redirects using inertia location header and creates payment record', function () {
    $product = Product::factory()->create([
        'price' => 50.00,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    $delivery = DeliveryOption::factory()->create(['price' => 5.00]);
    $payment = PaymentOption::factory()->stripe()->create(['is_active' => true]);

    $mockGateway = Mockery::mock(StripeGateway::class);
    $mockGateway->shouldReceive('createCheckout')
        ->once()
        ->andReturn(CheckoutResult::success(
            redirectUrl: 'https://checkout.stripe.com/c/pay/cs_test_session987',
            sessionId: 'cs_test_session987'
        ));

    $this->instance(StripeGateway::class, $mockGateway);

    $response = $this->withHeaders(['X-Inertia' => 'true'])->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ],
    ])->post(route('checkout.store'), [
        'customer_name' => 'Aurelia Birch',
        'customer_email' => 'aurelia@greenveil.test',
        'delivery_option_id' => $delivery->id,
        'payment_option_id' => $payment->id,
        'shipping_address' => [
            'line1' => '9 Ivy Crest',
            'city' => 'Cork',
            'postal_code' => 'T12 A3B4',
            'country' => 'IE',
        ],
        'billing_same_as_shipping' => true,
    ]);

    $response->assertStatus(409);
    $response->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/cs_test_session987');

    $order = Order::query()->where('customer_email', 'aurelia@greenveil.test')->first();
    expect($order)->not->toBeNull();
    expect($order->stripe_session_id)->toBe('cs_test_session987');
    expect($order->status)->toBe(OrderStatus::PendingPayment);
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);

    $paymentRecord = Payment::where('order_id', $order->id)->where('driver', 'stripe')->first();
    expect($paymentRecord)->not->toBeNull();
    expect($paymentRecord->stripe_session_id)->toBe('cs_test_session987');
    expect($paymentRecord->status)->toBe(PaymentStatus::Unpaid);
});

test('retry payment redirects to new stripe checkout session for pending order', function () {
    $paymentOption = PaymentOption::factory()->stripe()->create();
    $order = Order::factory()->create([
        'payment_option_id' => $paymentOption->id,
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    OrderItem::factory()->create(['order_id' => $order->id]);

    $mockGateway = Mockery::mock(StripeGateway::class);
    $mockGateway->shouldReceive('createCheckout')
        ->once()
        ->andReturn(CheckoutResult::success(
            redirectUrl: 'https://checkout.stripe.com/c/pay/cs_test_retry456',
            sessionId: 'cs_test_retry456'
        ));

    $this->instance(StripeGateway::class, $mockGateway);

    $response = $this->withHeaders(['X-Inertia' => 'true'])->post(route('checkout.retry', ['order' => $order->public_id]));

    $response->assertStatus(409);
    $response->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/cs_test_retry456');

    expect($order->fresh()->stripe_session_id)->toBe('cs_test_retry456');
});
