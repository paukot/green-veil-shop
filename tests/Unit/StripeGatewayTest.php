<?php

use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Payments\Stripe\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('creates checkout session with correct line items, amounts, metadata, expiry and idempotency key', function () {
    $delivery = DeliveryOption::factory()->create([
        'name' => 'Botanical Courier',
        'price' => 5.50,
    ]);

    $order = Order::factory()->create([
        'delivery_option_id' => $delivery->id,
        'customer_email' => 'lady.botanical@greenveil.test',
        'subtotal' => 60.00,
        'delivery_amount' => 5.50,
        'discount_amount' => 0.00,
        'total' => 65.50,
        'currency' => 'EUR',
    ]);

    $product = Product::factory()->create(['sku' => 'GV-ROSE-50']);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => 'Rose Otto Night Nectar',
        'product_sku' => 'GV-ROSE-50',
        'unit_price' => 30.00,
        'quantity' => 2,
        'subtotal' => 60.00,
    ]);

    $mockClient = Mockery::mock(StripeClient::class);
    $mockCheckout = Mockery::mock();
    $mockSessions = Mockery::mock();

    $mockClient->checkout = $mockCheckout;
    $mockCheckout->sessions = $mockSessions;

    $mockSessions->shouldReceive('create')
        ->once()
        ->withArgs(function (array $params, array $opts) use ($order) {
            // Check session mode and customer info
            expect($params['mode'])->toBe('payment');
            expect($params['adaptive_pricing']['enabled'])->toBeFalse();
            expect($params['customer_email'])->toBe('lady.botanical@greenveil.test');
            expect($params['client_reference_id'])->toBe((string) $order->id);

            // Check metadata
            expect($params['metadata']['order_id'])->toBe((string) $order->id);
            expect($params['metadata']['public_id'])->toBe($order->public_id);
            expect($params['metadata']['order_number'])->toBe($order->number);

            // Check URLs
            expect($params['success_url'])->toContain('/checkout/success?order='.$order->public_id);
            expect($params['cancel_url'])->toContain('/checkout/cancel?order='.$order->public_id);

            // Check expiry >= 30 minutes in future
            expect($params['expires_at'])->toBeGreaterThanOrEqual(time() + (30 * 60));

            // Check idempotency key
            expect($opts['idempotency_key'])->toBe("order_{$order->public_id}");

            // Check line items count (1 product line item + 1 shipping line item)
            expect($params['line_items'])->toHaveCount(2);

            // Product line item
            $productItem = $params['line_items'][0];
            expect($productItem['price_data']['product_data']['name'])->toBe('Rose Otto Night Nectar');
            expect($productItem['price_data']['product_data']['description'])->toBe('SKU: GV-ROSE-50');
            expect($productItem['price_data']['currency'])->toBe('eur');
            expect($productItem['price_data']['unit_amount'])->toBe(3000); // 30.00 * 100 = 3000 cents
            expect($productItem['quantity'])->toBe(2);

            // Shipping line item
            $shippingItem = $params['line_items'][1];
            expect($shippingItem['price_data']['product_data']['name'])->toBe('Shipping (Botanical Courier)');
            expect($shippingItem['price_data']['unit_amount'])->toBe(550); // 5.50 * 100 = 550 cents
            expect($shippingItem['quantity'])->toBe(1);

            // Total minor units
            $totalCents = ($productItem['price_data']['unit_amount'] * $productItem['quantity'])
                + ($shippingItem['price_data']['unit_amount'] * $shippingItem['quantity']);
            expect($totalCents)->toBe(6550); // Exactly 65.50 EUR

            return true;
        })
        ->andReturn((object) [
            'id' => 'cs_test_mocked12345',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_mocked12345',
        ]);

    $gateway = new StripeGateway($mockClient);
    $result = $gateway->createCheckout($order);

    expect($result->successful)->toBeTrue();
    expect($result->sessionId)->toBe('cs_test_mocked12345');
    expect($result->redirectUrl)->toBe('https://checkout.stripe.com/c/pay/cs_test_mocked12345');
});

test('creates one-time coupon and attaches discount when discount is present on order', function () {
    $delivery = DeliveryOption::factory()->create([
        'name' => 'Eco Standard',
        'price' => 4.00,
    ]);

    // Order with €10 discount on €50 subtotal + €4 delivery = €44.00
    $order = Order::factory()->create([
        'delivery_option_id' => $delivery->id,
        'subtotal' => 50.00,
        'discount_amount' => 10.00,
        'delivery_amount' => 4.00,
        'total' => 44.00,
        'currency' => 'EUR',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Jasmine Serum',
        'unit_price' => 50.00,
        'quantity' => 1,
        'subtotal' => 50.00,
    ]);

    $mockClient = Mockery::mock(StripeClient::class);
    $mockCheckout = Mockery::mock();
    $mockSessions = Mockery::mock();
    $mockCoupons = Mockery::mock();

    $mockClient->checkout = $mockCheckout;
    $mockCheckout->sessions = $mockSessions;
    $mockClient->coupons = $mockCoupons;

    $mockCoupons->shouldReceive('create')
        ->once()
        ->withArgs(function (array $params, array $opts) use ($order) {
            expect($params['amount_off'])->toBe(1000); // €10.00 in cents
            expect($params['currency'])->toBe('eur');
            expect($params['duration'])->toBe('once');
            expect($opts['idempotency_key'])->toBe("order_coupon_{$order->public_id}_1000");

            return true;
        })
        ->andReturn((object) ['id' => 'coupon_test_abc123']);

    $mockSessions->shouldReceive('create')
        ->once()
        ->withArgs(function (array $params) {
            // Line items retain their full price
            expect($params['line_items'][0]['price_data']['unit_amount'])->toBe(5000);
            expect($params['line_items'][1]['price_data']['unit_amount'])->toBe(400);

            // Discount attached via coupon
            expect($params['discounts'])->toBe([
                ['coupon' => 'coupon_test_abc123'],
            ]);

            return true;
        })
        ->andReturn((object) [
            'id' => 'cs_test_discounted',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_discounted',
        ]);

    $gateway = new StripeGateway($mockClient);
    $result = $gateway->createCheckout($order);

    expect($result->successful)->toBeTrue();
});

test('returns failed checkout result when Stripe API throws an exception', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $mockClient = Mockery::mock(StripeClient::class);
    $mockCheckout = Mockery::mock();
    $mockSessions = Mockery::mock();

    $mockClient->checkout = $mockCheckout;
    $mockCheckout->sessions = $mockSessions;

    $mockSessions->shouldReceive('create')
        ->once()
        ->andThrow(InvalidRequestException::factory('Stripe authentication key is invalid or restricted.'));

    $gateway = new StripeGateway($mockClient);
    $result = $gateway->createCheckout($order);

    expect($result->successful)->toBeFalse();
    expect($result->errorMessage)->toContain('Stripe authentication key is invalid or restricted.');
});

test('processes full refund via Stripe refunds API', function () {
    $order = Order::factory()->create([
        'stripe_payment_intent_id' => 'pi_test_full_refund_123',
        'total' => 85.00,
    ]);

    $mockClient = Mockery::mock(StripeClient::class);
    $mockRefunds = Mockery::mock();
    $mockClient->refunds = $mockRefunds;

    $mockRefunds->shouldReceive('create')
        ->once()
        ->withArgs(function (array $params) use ($order) {
            expect($params['payment_intent'])->toBe('pi_test_full_refund_123');
            expect($params['amount'])->toBe(8500);
            expect($params['reason'])->toBe('requested_by_customer');
            expect($params['metadata']['order_id'])->toBe((string) $order->id);

            return true;
        })
        ->andReturn((object) [
            'id' => 're_test_full_refund_999',
            'status' => 'succeeded',
        ]);

    $gateway = new StripeGateway($mockClient);
    $result = $gateway->refund($order, 8500, 'requested_by_customer');

    expect($result->successful)->toBeTrue();
    expect($result->refundId)->toBe('re_test_full_refund_999');
});

test('processes partial refund with specific amount via Stripe refunds API', function () {
    $order = Order::factory()->create([
        'stripe_payment_intent_id' => 'pi_test_partial_refund_456',
        'total' => 120.00,
    ]);

    $mockClient = Mockery::mock(StripeClient::class);
    $mockRefunds = Mockery::mock();
    $mockClient->refunds = $mockRefunds;

    $mockRefunds->shouldReceive('create')
        ->once()
        ->withArgs(function (array $params) {
            expect($params['payment_intent'])->toBe('pi_test_partial_refund_456');
            expect($params['amount'])->toBe(2500);
            expect($params['reason'])->toBe('duplicate');

            return true;
        })
        ->andReturn((object) [
            'id' => 're_test_partial_refund_888',
            'status' => 'succeeded',
        ]);

    $gateway = new StripeGateway($mockClient);
    $result = $gateway->refund($order, 2500, 'duplicate');

    expect($result->successful)->toBeTrue();
    expect($result->refundId)->toBe('re_test_partial_refund_888');
});

test('returns failed refund result when Stripe API throws an exception', function () {
    $order = Order::factory()->create([
        'stripe_payment_intent_id' => 'pi_test_error_refund',
    ]);

    $mockClient = Mockery::mock(StripeClient::class);
    $mockRefunds = Mockery::mock();
    $mockClient->refunds = $mockRefunds;

    $mockRefunds->shouldReceive('create')
        ->once()
        ->andThrow(InvalidRequestException::factory('This payment has already been refunded.'));

    $gateway = new StripeGateway($mockClient);
    $result = $gateway->refund($order, 1000);

    expect($result->successful)->toBeFalse();
    expect($result->errorMessage)->toContain('This payment has already been refunded.');
});
