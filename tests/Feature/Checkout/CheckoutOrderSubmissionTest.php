<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\User;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\Stripe\StripeGateway;

describe('Checkout Order Submission', function () {
    it('validates required fields on checkout store', function () {
        $response = $this->post(route('checkout.store'), []);

        $response->assertSessionHasErrors([
            'customer_name',
            'customer_email',
            'delivery_option_id',
            'payment_option_id',
            'shipping_address',
            'shipping_address.line1',
            'shipping_address.city',
            'shipping_address.postal_code',
            'shipping_address.country',
        ]);
    });

    it('rejects order submission when shipping country is not available for shipping', function () {
        $response = $this->post(route('checkout.store'), [
            'shipping_address' => [
                'country' => 'US', // US is active, but is_shipping_available is false
            ],
        ]);

        $response->assertSessionHasErrors(['shipping_address.country']);
    });

    it('rejects order placement when item becomes out of stock during submission', function () {
        $product = Product::factory()->create([
            'stock_quantity' => 1,
            'track_inventory' => true,
            'is_active' => true,
        ]);
        $delivery = DeliveryOption::factory()->standard()->create();
        $payment = PaymentOption::factory()->bankTransfer()->create();

        $sessionCart = [
            'cart' => [
                $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 5],
            ],
        ];

        $payload = [
            'customer_name' => 'Rosemary Green',
            'customer_email' => 'rosemary@example.com',
            'customer_phone' => '+353 1 234 5678',
            'delivery_option_id' => $delivery->id,
            'payment_option_id' => $payment->id,
            'shipping_address' => [
                'line1' => '10 Botanical Way',
                'city' => 'Galway',
                'postal_code' => 'H91 AB12',
                'country' => 'IE',
            ],
            'billing_address' => [
                'line1' => '10 Botanical Way',
                'city' => 'Galway',
                'postal_code' => 'H91 AB12',
                'country' => 'IE',
            ],
        ];

        $response = $this->withSession($sessionCart)->post(route('checkout.store'), $payload);

        $response->assertSessionHasErrors(['cart']);
        expect(Order::count())->toBe(0);
    });

    it('completes order via bank transfer and redirects to success page', function () {
        $product = Product::factory()->create([
            'price' => 40.00,
            'stock_quantity' => 5,
            'track_inventory' => true,
            'is_active' => true,
        ]);
        $delivery = DeliveryOption::factory()->create(['price' => 5.00, 'free_threshold' => 100.00]);
        $payment = PaymentOption::factory()->bankTransfer()->create();

        $sessionCart = [
            'cart' => [
                $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 2],
            ],
        ];

        $payload = [
            'customer_name' => 'Fiona MacLeod',
            'customer_email' => 'fiona@example.com',
            'customer_phone' => '+353 1 234 5678',
            'delivery_option_id' => $delivery->id,
            'payment_option_id' => $payment->id,
            'shipping_address' => [
                'line1' => '12 Willow Lane',
                'city' => 'Cork',
                'postal_code' => 'T12 AB34',
                'country' => 'IE',
            ],
            'billing_address' => [
                'line1' => '12 Willow Lane',
                'city' => 'Cork',
                'postal_code' => 'T12 AB34',
                'country' => 'IE',
            ],
        ];

        $response = $this->withSession($sessionCart)->post(route('checkout.store'), $payload);

        $order = Order::first();
        expect($order)->not->toBeNull();
        expect($order->status)->toBe(OrderStatus::PendingPayment);
        expect($order->payment_status)->toBe(PaymentStatus::Unpaid);
        expect((float) $order->total)->toBe(85.00);

        expect($product->fresh()->stock_quantity)->toBe(3);

        $response->assertRedirect(route('checkout.success', ['order' => $order->public_id]));
    });

    it('initiates Stripe checkout session and redirects to Stripe URL', function () {
        $product = Product::factory()->price(50.00)->create([
            'stock_quantity' => 10,
            'track_inventory' => true,
            'is_active' => true,
        ]);
        $delivery = DeliveryOption::factory()->standard()->create(['price' => 5.00]);
        $payment = PaymentOption::factory()->stripe()->create();

        $mockGateway = Mockery::mock(StripeGateway::class);
        $mockGateway->shouldReceive('createCheckout')
            ->once()
            ->andReturn(CheckoutResult::success(
                redirectUrl: 'https://checkout.stripe.com/pay/cs_test_mock_session_123',
                sessionId: 'cs_test_mock_session_123',
            ));
        $this->app->instance(StripeGateway::class, $mockGateway);

        $sessionCart = [
            'cart' => [
                $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $payload = [
            'customer_name' => 'Claire Fraser',
            'customer_email' => 'claire@example.com',
            'delivery_option_id' => $delivery->id,
            'payment_option_id' => $payment->id,
            'shipping_address' => [
                'line1' => '1 High Street',
                'city' => 'Inverness',
                'postal_code' => 'IV1 1AA',
                'country' => 'GB',
            ],
            'billing_address' => [
                'line1' => '1 High Street',
                'city' => 'Inverness',
                'postal_code' => 'IV1 1AA',
                'country' => 'GB',
            ],
        ];

        $response = $this->withSession($sessionCart)->post(route('checkout.store'), $payload);

        $order = Order::first();
        expect($order)->not->toBeNull();

        $paymentRecord = Payment::where('order_id', $order->id)->first();
        expect($paymentRecord)->not->toBeNull();
        expect($paymentRecord->stripe_session_id)->toBe('cs_test_mock_session_123');
        expect($paymentRecord->status)->toBe(PaymentStatus::Unpaid);

        $response->assertRedirect('https://checkout.stripe.com/pay/cs_test_mock_session_123');
    });

    it('associates authenticated user with placed order', function () {
        $user = User::factory()->create();
        $product = Product::factory()->price(30.00)->create([
            'stock_quantity' => 5,
            'track_inventory' => true,
            'is_active' => true,
        ]);
        $delivery = DeliveryOption::factory()->standard()->create(['price' => 5.00]);
        $payment = PaymentOption::factory()->bankTransfer()->create();

        CartItem::factory()->forUser($user)->forProduct($product, 1)->create();

        $payload = [
            'customer_name' => 'Eleanor Vance',
            'customer_email' => $user->email,
            'delivery_option_id' => $delivery->id,
            'payment_option_id' => $payment->id,
            'shipping_address' => [
                'line1' => '44 Herbarium Way',
                'city' => 'Dublin',
                'postal_code' => 'D04 K382',
                'country' => 'IE',
            ],
            'billing_address' => [
                'line1' => '44 Herbarium Way',
                'city' => 'Dublin',
                'postal_code' => 'D04 K382',
                'country' => 'IE',
            ],
        ];

        $response = actingAsCustomer($user)->post(route('checkout.store'), $payload);

        $order = Order::first();
        expect($order->user_id)->toBe($user->id);
        $response->assertRedirect(route('checkout.success', ['order' => $order->public_id]));
    });

    describe('Order Confirmation Access Control', function () {
        it('allows guest to access guest-placed order confirmation page', function () {
            $order = Order::factory()->create([
                'user_id' => null,
                'customer_name' => 'Guest Shopper',
            ]);

            $response = $this->get(route('checkout.success', ['order' => $order->public_id]));
            $response->assertOk();
        });

        it('allows authenticated user to access their own order confirmation page', function () {
            $user = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);

            $response = actingAsCustomer($user)->get(route('checkout.success', ['order' => $order->public_id]));
            $response->assertOk();
        });

        it('forbids authenticated user from viewing another users order confirmation page', function () {
            $owner = User::factory()->create();
            $intruder = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $owner->id,
            ]);

            $response = actingAsCustomer($intruder)->get(route('checkout.success', ['order' => $order->public_id]));
            $response->assertForbidden();
        });
    });
});
