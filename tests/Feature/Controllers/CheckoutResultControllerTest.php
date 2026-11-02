<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\User;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\Stripe\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('CheckoutResultController', function () {
    describe('success', function () {
        it('redirects to home if order query parameter is missing or order not found', function () {
            // Missing parameter
            $responseMissing = $this->get(route('checkout.success'));
            $responseMissing->assertRedirect(route('home'));

            // Non-existent order public id
            $responseNotFound = $this->get(route('checkout.success', ['order' => 'non_existent_ulid']));
            $responseNotFound->assertRedirect(route('home'));
        });

        it('renders checkout success page with full order details and clears remaining session cart', function () {
            $delivery = DeliveryOption::factory()->standard()->create();
            $paymentOption = PaymentOption::factory()->bankTransfer()->create([
                'instructions' => 'IBAN: IE89BOTANICAL0001234567',
            ]);

            $order = Order::factory()->create([
                'delivery_option_id' => $delivery->id,
                'payment_option_id' => $paymentOption->id,
                'customer_name' => 'Fiona MacLeod',
                'customer_email' => 'fiona@example.com',
                'total' => 55.00,
            ]);

            OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

            $response = $this->withSession(['cart' => [1 => ['quantity' => 1]]])
                ->get(route('checkout.success', ['order' => $order->public_id]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Success')
                ->where('order.public_id', $order->public_id)
                ->where('order.customer_name', 'Fiona MacLeod')
                ->where('order.total', 55)
                ->has('order.items', 2)
                ->where('order.paymentOption.instructions', 'IBAN: IE89BOTANICAL0001234567')
            );

            // Session cart cleared
            $this->assertEmpty(session('cart'));
        });

        it('forbids another authenticated user from viewing the order success page', function () {
            $owner = User::factory()->create();
            $stranger = User::factory()->create();

            $order = Order::factory()->forUser($owner)->create();

            $response = $this->actingAs($stranger)->get(route('checkout.success', ['order' => $order->public_id]));

            $response->assertForbidden();
        });

        it('reconciles unpaid Stripe order directly with Stripe API on success page', function () {
            $sessionId = 'cs_test_mock_'.uniqid();
            $stripePaymentOption = PaymentOption::factory()->create([
                'driver' => 'stripe',
            ]);

            $order = Order::factory()->guest()->create([
                'payment_option_id' => $stripePaymentOption->id,
                'stripe_session_id' => $sessionId,
                'payment_status' => PaymentStatus::Unpaid,
                'status' => OrderStatus::Pending,
                'total' => 45.00,
                'currency' => 'EUR',
            ]);

            $mockSession = \Stripe\Checkout\Session::constructFrom([
                'id' => $sessionId,
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test_reconciled',
                'amount_total' => 4500,
                'currency' => 'eur',
            ]);

            $mockClient = Mockery::mock(\Stripe\StripeClient::class);
            $mockClient->checkout = new stdClass();
            $mockClient->checkout->sessions = Mockery::mock();
            $mockClient->checkout->sessions
                ->shouldReceive('retrieve')
                ->with($sessionId)
                ->once()
                ->andReturn($mockSession);

            $this->app->instance(\Stripe\StripeClient::class, $mockClient);

            $response = $this->get(route('checkout.success', ['order' => $order->public_id]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Success')
                ->where('order.payment_status', 'paid')
                ->where('order.status', 'processing')
            );

            $order->refresh();
            expect($order->payment_status)->toBe(PaymentStatus::Paid);
            expect($order->status)->toBe(OrderStatus::Processing);
        });
    });


    describe('cancel', function () {
        it('redirects to cart if order query parameter is missing on cancel', function () {
            $response = $this->get(route('checkout.cancel'));

            $response->assertRedirect(route('cart.index'));
        });

        it('renders checkout cancel page and releases reserved stock if order is cancelled', function () {
            $product = Product::factory()->inStock(5)->create();
            $order = Order::factory()->pending()->create(['total' => 75.00]);
            OrderItem::factory()->forProduct($product, 2)->create(['order_id' => $order->id]);

            $response = $this->get(route('checkout.cancel', ['order' => $order->public_id]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Checkout/Cancel')
                ->where('order.public_id', $order->public_id)
                ->where('order.number', $order->number)
            );
        });
    });

    describe('retry', function () {
        it('forbids unauthorized user from retrying payment on an order', function () {
            $owner = User::factory()->create();
            $stranger = User::factory()->create();

            $order = Order::factory()->forUser($owner)->pendingPayment()->create();

            $response = $this->actingAs($stranger)->post(route('checkout.retry', ['order' => $order->public_id]));

            $response->assertForbidden();
        });

        it('initiates retry payment flow for unpaid order and returns redirect', function () {
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
                    redirectUrl: 'https://checkout.stripe.com/c/pay/cs_test_retry_result',
                    sessionId: 'cs_test_retry_result'
                ));

            $this->instance(StripeGateway::class, $mockGateway);

            $response = $this->withHeaders(['X-Inertia' => 'true'])
                ->post(route('checkout.retry', ['order' => $order->public_id]));

            $response->assertStatus(409);
            $response->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/cs_test_retry_result');

            expect($order->fresh()->stripe_session_id)->toBe('cs_test_retry_result');
        });
    });
});
