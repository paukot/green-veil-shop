<?php

use App\Filament\Resources\StripeEvents\Pages\ListStripeEvents;
use App\Models\Order;
use App\Models\StripeEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('StripeEventResource in Filament', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
    });

    it('denies access to non-admins', function () {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/stripe-events')
            ->assertForbidden();
    });

    it('renders list page with incoming webhook events', function () {
        $order = Order::factory()->create();

        $eventLinked = StripeEvent::create([
            'order_id' => $order->id,
            'stripe_event_id' => 'evt_linked_order_123',
            'type' => 'checkout.session.completed',
            'processed_at' => now(),
            'payload' => [
                'id' => 'evt_linked_order_123',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'amount_total' => 3500,
                        'currency' => 'eur',
                        'payment_status' => 'paid',
                    ],
                ],
            ],
        ]);

        $eventOrphan = StripeEvent::create([
            'order_id' => null,
            'stripe_event_id' => 'evt_orphan_system_456',
            'type' => 'charge.refunded',
            'payload' => [
                'id' => 'evt_orphan_system_456',
                'type' => 'charge.refunded',
                'data' => [
                    'object' => [
                        'amount_refunded' => 1500,
                        'currency' => 'eur',
                    ],
                ],
            ],
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/stripe-events')
            ->assertOk()
            ->assertSee('checkout.session.completed')
            ->assertSee('charge.refunded');

        Livewire::actingAs($this->admin)
            ->test(ListStripeEvents::class)
            ->assertCanSeeTableRecords([$eventLinked, $eventOrphan])
            ->assertSee($order->number)
            ->assertSee('Unassigned')
            ->assertSee('Checkout Completed')
            ->assertSee('Refund Processed');
    });

    it('allows inspecting sanitized webhook payload in modal without sensitive data', function () {
        $event = StripeEvent::create([
            'stripe_event_id' => 'evt_sensitive_test',
            'type' => 'checkout.session.completed',
            'payload' => [
                'id' => 'evt_sensitive_test',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_test_modal_inspect',
                        'amount_total' => 5900,
                        'client_ip' => '198.51.100.22',
                        'payment_method_details' => [
                            'card' => [
                                'brand' => 'visa',
                                'last4' => '1234',
                                'fingerprint' => 'hidden_fingerprint_token',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListStripeEvents::class)
            ->mountTableAction('inspect', $event)
            ->assertSee('checkout.session.completed')
            ->assertSee('Visa •••• 1234')
            ->assertDontSee('198.51.100.22')
            ->assertDontSee('hidden_fingerprint_token');
    });
});
