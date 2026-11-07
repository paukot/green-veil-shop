<?php

use App\Actions\Checkout\MarkOrderPaid;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\DeliveryOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentOption;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;

test('order confirmation email renders with order details, items, and instructions', function () {
    $delivery = DeliveryOption::factory()->create([
        'name' => 'Royal Botanical Delivery',
        'price' => 6.00,
    ]);

    $payment = PaymentOption::factory()->bankTransfer()->create([
        'name' => 'Direct Bank Transfer',
        'instructions' => 'IBAN: IE89BOTANICAL999999',
    ]);

    $order = Order::factory()->create([
        'customer_name' => 'Lady Elizabeth',
        'customer_email' => 'elizabeth@botanicals.test',
        'delivery_option_id' => $delivery->id,
        'payment_option_id' => $payment->id,
        'subtotal' => 50.00,
        'delivery_amount' => 6.00,
        'discount_amount' => 5.00,
        'total' => 51.00,
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'shipping_address' => [
            'line1' => '10 Primrose Terrace',
            'city' => 'Dublin',
            'postal_code' => 'D06 X892',
            'country' => 'IE',
        ],
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Elderberry Cleansing Oil',
        'product_sku' => 'GV-ELDER-100',
        'unit_price' => 50.00,
        'quantity' => 1,
        'subtotal' => 50.00,
    ]);

    $mailable = new OrderConfirmationMail($order);

    $mailable->assertHasSubject("Your Green Veil Order #{$order->number} Confirmation");
    $mailable->assertSeeInHtml('Lady Elizabeth');
    $mailable->assertSeeInHtml($order->number);
    $mailable->assertSeeInHtml('Elderberry Cleansing Oil');
    $mailable->assertSeeInHtml('GV-ELDER-100');
    $mailable->assertSeeInHtml('IBAN: IE89BOTANICAL999999');
    $mailable->assertSeeInHtml('10 Primrose Terrace');
    $mailable->assertSeeInHtml('&euro;51.00', false);
});

test('bank transfer order queues confirmation email to customer', function () {
    Mail::fake();

    $product = Product::factory()->create(['price' => 45.00, 'is_active' => true]);
    $delivery = DeliveryOption::factory()->create(['price' => 5.00]);
    $payment = PaymentOption::factory()->bankTransfer()->create();

    $this->withSession([
        'cart' => [
            $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
        ],
    ])->post(route('checkout.store'), [
        'customer_name' => 'Lady Rosalind',
        'customer_email' => 'rosalind@botanicals.test',
        'delivery_option_id' => $delivery->id,
        'payment_option_id' => $payment->id,
        'shipping_address' => [
            'line1' => '12 Rosewood Manor',
            'city' => 'Galway',
            'postal_code' => 'H91 E4X2',
            'country' => 'IE',
        ],
        'billing_same_as_shipping' => true,
    ]);

    Mail::assertQueued(OrderConfirmationMail::class, function ($mail) {
        return $mail->hasTo('rosalind@botanicals.test');
    });
});

test('marking stripe order paid queues confirmation email', function () {
    Mail::fake();

    $order = Order::factory()->create([
        'customer_email' => 'margot@greenveil.test',
        'status' => OrderStatus::PendingPayment,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => 60.00,
        'currency' => 'EUR',
    ]);

    $action = app(MarkOrderPaid::class);
    $action->execute($order, [
        'id' => 'cs_test_confirm_mail',
        'payment_intent' => 'pi_test_confirm_mail',
        'amount_total' => 6000,
        'currency' => 'eur',
    ]);

    Mail::assertQueued(OrderConfirmationMail::class, function ($mail) {
        return $mail->hasTo('margot@greenveil.test');
    });
});
