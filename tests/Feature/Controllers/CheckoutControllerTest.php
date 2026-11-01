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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('CheckoutController', function () {
    describe('show', function () {
        it('redirects to cart if shopping bag is empty', function () {
            $response = $this->get(route('checkout.show'));

            $response->assertRedirect(route('cart.index'));
            $response->assertSessionHas('info');
        });

        it('renders checkout page with items, delivery options, and payment options for guest', function () {
            $product = Product::factory()->price(40.00)->create(['name' => 'Rose Serum']);
            $delivery = DeliveryOption::factory()->standard()->create();
            $payment = PaymentOption::factory()->bankTransfer()->create();

            $response = $this->withSession([
                'cart' => [
                    $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
                ],
            ])->get(route('checkout.show'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Index')
                ->has('items', 1)
                ->where('items.0.product.name', 'Rose Serum')
                ->has('deliveryOptions')
                ->has('paymentOptions')
                ->where('customer', null)
            );
        });

        it('prefills customer info and addresses when user is authenticated', function () {
            $user = User::factory()->create([
                'name' => 'Eleanor Vance',
                'email' => 'eleanor@example.com',
            ]);

            $product = Product::factory()->create();
            CartItem::factory()->forUser($user)->forProduct($product, 1)->create();
            DeliveryOption::factory()->standard()->create();
            PaymentOption::factory()->stripe()->create();

            $response = $this->actingAs($user)->get(route('checkout.show'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Index')
                ->where('customer.name', 'Eleanor Vance')
                ->where('customer.email', 'eleanor@example.com')
            );
        });
    });

    describe('store', function () {
        it('validates all required checkout fields', function () {
            $response = $this->post(route('checkout.store'), []);

            $response->assertSessionHasErrors([
                'customer_name',
                'customer_email',
                'delivery_option_id',
                'payment_option_id',
                'shipping_address',
            ]);
        });

        it('rejects inactive delivery option', function () {
            $product = Product::factory()->inStock(10)->create();
            $inactiveDelivery = DeliveryOption::factory()->inactive()->create();
            $activePayment = PaymentOption::factory()->bankTransfer()->create();

            $response = $this->withSession([
                'cart' => [
                    $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
                ],
            ])->post(route('checkout.store'), [
                'customer_name' => 'Alice Gray',
                'customer_email' => 'alice@example.com',
                'delivery_option_id' => $inactiveDelivery->id,
                'payment_option_id' => $activePayment->id,
                'shipping_address' => [
                    'line1' => '10 Floral St',
                    'city' => 'Dublin',
                    'postal_code' => 'D01 A1B2',
                    'country' => 'IE',
                ],
                'billing_same_as_shipping' => true,
            ]);

            $response->assertSessionHasErrors(['delivery_option_id']);
        });

        it('rejects inactive payment option', function () {
            $product = Product::factory()->inStock(10)->create();
            $activeDelivery = DeliveryOption::factory()->standard()->create();
            $inactivePayment = PaymentOption::factory()->inactive()->create();

            $response = $this->withSession([
                'cart' => [
                    $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
                ],
            ])->post(route('checkout.store'), [
                'customer_name' => 'Alice Gray',
                'customer_email' => 'alice@example.com',
                'delivery_option_id' => $activeDelivery->id,
                'payment_option_id' => $inactivePayment->id,
                'shipping_address' => [
                    'line1' => '10 Floral St',
                    'city' => 'Dublin',
                    'postal_code' => 'D01 A1B2',
                    'country' => 'IE',
                ],
                'billing_same_as_shipping' => true,
            ]);

            $response->assertSessionHasErrors(['payment_option_id']);
        });

        it('places order successfully with Cash on Delivery / Bank Transfer and clears cart', function () {
            $product = Product::factory()->price(40.00)->inStock(10)->create(['name' => 'Hydrating Toner']);
            $delivery = DeliveryOption::factory()->create(['price' => 5.00, 'free_threshold' => 100.00]);
            $payment = PaymentOption::factory()->cod()->create();

            $response = $this->withSession([
                'cart' => [
                    $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 2],
                ],
            ])->post(route('checkout.store'), [
                'customer_name' => 'Thomas Thorne',
                'customer_email' => 'thomas@example.com',
                'delivery_option_id' => $delivery->id,
                'payment_option_id' => $payment->id,
                'shipping_address' => [
                    'line1' => '22 Botanical Row',
                    'city' => 'Cork',
                    'postal_code' => 'T12 B3C4',
                    'country' => 'IE',
                ],
                'billing_same_as_shipping' => true,
            ]);

            $order = Order::query()->where('customer_email', 'thomas@example.com')->first();
            expect($order)->not->toBeNull()
                ->and($order->status)->toBe(OrderStatus::Processing)
                ->and($order->payment_status)->toBe(PaymentStatus::Unpaid)
                ->and((float) $order->subtotal)->toBe(80.00)
                ->and((float) $order->delivery_amount)->toBe(5.00)
                ->and((float) $order->total)->toBe(85.00)
                ->and($product->fresh()->stock_quantity)->toBe(8);

            // Payment transaction created
            $paymentRecord = Payment::where('order_id', $order->id)->first();
            expect($paymentRecord)->not->toBeNull()
                ->and($paymentRecord->driver)->toBe('cod');

            // Session cart emptied
            $this->assertEmpty(session('cart'));

            $response->assertRedirect(route('checkout.success', ['order' => $order->public_id]));
        });

        it('places order with Stripe and returns redirect to checkout session', function () {
            $product = Product::factory()->price(50.00)->inStock(10)->create();
            $delivery = DeliveryOption::factory()->create(['price' => 4.99]);
            $payment = PaymentOption::factory()->stripe()->create();

            $mockGateway = Mockery::mock(StripeGateway::class);
            $mockGateway->shouldReceive('createCheckout')
                ->once()
                ->andReturn(CheckoutResult::success(
                    redirectUrl: 'https://checkout.stripe.com/c/pay/cs_test_sample',
                    sessionId: 'cs_test_sample'
                ));

            $this->instance(StripeGateway::class, $mockGateway);

            $response = $this->withHeaders(['X-Inertia' => 'true'])->withSession([
                'cart' => [
                    $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
                ],
            ])->post(route('checkout.store'), [
                'customer_name' => 'Sarah Connor',
                'customer_email' => 'sarah@example.com',
                'delivery_option_id' => $delivery->id,
                'payment_option_id' => $payment->id,
                'shipping_address' => [
                    'line1' => '100 Sunset Blvd',
                    'city' => 'Dublin',
                    'postal_code' => 'D02 Y1Z2',
                    'country' => 'IE',
                ],
                'billing_same_as_shipping' => true,
            ]);

            $response->assertStatus(409);
            $response->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/cs_test_sample');

            $order = Order::query()->where('customer_email', 'sarah@example.com')->first();
            expect($order)->not->toBeNull()
                ->and($order->stripe_session_id)->toBe('cs_test_sample')
                ->and($order->status)->toBe(OrderStatus::PendingPayment);
        });

        it('fails validation if inventory stock becomes insufficient before placing order', function () {
            $product = Product::factory()->inStock(1)->create();
            $delivery = DeliveryOption::factory()->create();
            $payment = PaymentOption::factory()->bankTransfer()->create();

            $response = $this->withSession([
                'cart' => [
                    $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 5],
                ],
            ])->post(route('checkout.store'), [
                'customer_name' => 'Late Customer',
                'customer_email' => 'late@example.com',
                'delivery_option_id' => $delivery->id,
                'payment_option_id' => $payment->id,
                'shipping_address' => [
                    'line1' => '5 Orchard Way',
                    'city' => 'Galway',
                    'postal_code' => 'H91 A1B2',
                    'country' => 'IE',
                ],
                'billing_same_as_shipping' => true,
            ]);

            $response->assertSessionHasErrors(['cart']);
        });
    });
});
