<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Orders\RelationManagers\StripeEventsRelationManager;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentOption;
use App\Models\StripeEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('OrderPaymentLogs in Filament', function () {
    beforeEach(function () {
        $this->admin = AdminUser::factory()->superAdmin()->create();
    });

    it('renders StripeEventsRelationManager with customer flow events', function () {
        $paymentOption = PaymentOption::factory()->stripe()->create();
        $order = Order::factory()->create([
            'payment_option_id' => $paymentOption->id,
            'stripe_session_id' => 'cs_test_session_123',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
            'total' => 29.89,
        ]);

        $event = StripeEvent::create([
            'order_id' => $order->id,
            'stripe_event_id' => 'evt_test_checkout_done',
            'type' => 'checkout.session.completed',
            'processed_at' => now(),
            'payload' => [
                'id' => 'evt_test_checkout_done',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_test_session_123',
                        'amount_total' => 2989,
                        'currency' => 'eur',
                        'payment_status' => 'paid',
                        'payment_method_details' => [
                            'card' => [
                                'brand' => 'visa',
                                'last4' => '4242',
                            ],
                        ],
                        'outcome' => [
                            'seller_message' => 'Payment complete.',
                        ],
                    ],
                ],
            ],
        ]);

        // Unrelated event for another order
        $otherOrder = Order::factory()->create();
        $otherEvent = StripeEvent::create([
            'order_id' => $otherOrder->id,
            'stripe_event_id' => 'evt_other_order',
            'type' => 'charge.refunded',
            'payload' => ['id' => 'evt_other_order'],
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(StripeEventsRelationManager::class, [
                'ownerRecord' => $order,
                'pageClass' => EditOrder::class,
            ])
            ->assertCanSeeTableRecords([$event])
            ->assertCanNotSeeTableRecords([$otherEvent])
            ->assertSee('Checkout Completed')
            ->assertSee('Visa •••• 4242')
            ->assertSee('€29.89');
    });

    it('renders PaymentsRelationManager with gateway records', function () {
        $order = Order::factory()->create([
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
            'total' => 54.99,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'driver' => 'stripe',
            'stripe_session_id' => 'cs_test_payment_attempt',
            'stripe_payment_intent_id' => 'pi_test_intent_ok',
            'status' => PaymentStatus::Paid,
            'amount' => 54.99,
            'currency' => 'EUR',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(PaymentsRelationManager::class, [
                'ownerRecord' => $order,
                'pageClass' => EditOrder::class,
            ])
            ->assertCanSeeTableRecords([$payment])
            ->assertSee('STRIPE')
            ->assertSee('Paid')
            ->assertSee('pi_test_intent_ok');
    });

    it('allows admin to inspect a Stripe event and see sanitized data without PII leakage', function () {
        $order = Order::factory()->create();

        $event = StripeEvent::create([
            'order_id' => $order->id,
            'stripe_event_id' => 'evt_test_inspect_safe',
            'type' => 'checkout.session.completed',
            'payload' => [
                'id' => 'evt_test_inspect_safe',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_test_inspect_safe',
                        'amount_total' => 1999,
                        'currency' => 'eur',
                        'client_ip' => '203.0.113.195',
                        'payment_method_details' => [
                            'card' => [
                                'brand' => 'mastercard',
                                'last4' => '8888',
                                'fingerprint' => 'super_secret_fingerprint',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(StripeEventsRelationManager::class, [
                'ownerRecord' => $order,
                'pageClass' => EditOrder::class,
            ])
            ->mountTableAction('inspect', $event)
            ->assertSee('checkout.session.completed')
            ->assertSee('Mastercard •••• 8888')
            ->assertDontSee('203.0.113.195')
            ->assertDontSee('super_secret_fingerprint');
    });
});
