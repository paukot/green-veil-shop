<?php

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Livewire\Livewire;

describe('PaymentResource in Filament', function () {
    beforeEach(function () {
        $this->admin = AdminUser::factory()->superAdmin()->create();
    });

    it('denies access to non-admins', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->get('/admin/payments')
            ->assertRedirect('/admin/login');
    });

    it('renders global transactions table with payments across orders', function () {
        $order1 = Order::factory()->create(['customer_name' => 'Amy Pond']);
        $payment1 = Payment::create([
            'order_id' => $order1->id,
            'driver' => 'stripe',
            'amount' => 45.00,
            'currency' => 'EUR',
            'status' => PaymentStatus::Paid,
            'stripe_payment_intent_id' => 'pi_test_pond_123',
            'paid_at' => now(),
        ]);

        $order2 = Order::factory()->create(['customer_name' => 'Rory Williams']);
        $payment2 = Payment::create([
            'order_id' => $order2->id,
            'driver' => 'bank_transfer',
            'amount' => 75.00,
            'currency' => 'EUR',
            'status' => PaymentStatus::Unpaid,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get('/admin/payments')
            ->assertOk()
            ->assertSee($order1->number)
            ->assertSee($order2->number)
            ->assertSee('Amy Pond')
            ->assertSee('Rory Williams');

        Livewire::actingAs($this->admin, 'admin')
            ->test(ListPayments::class)
            ->assertCanSeeTableRecords([$payment1, $payment2])
            ->assertSee('STRIPE')
            ->assertSee('BANK_TRANSFER')
            ->assertSee('€45.00')
            ->assertSee('€75.00');
    });

    it('allows inspecting a transaction details modal', function () {
        $order = Order::factory()->create(['customer_name' => 'River Song']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'driver' => 'stripe',
            'amount' => 120.00,
            'currency' => 'EUR',
            'status' => PaymentStatus::Paid,
            'stripe_payment_intent_id' => 'pi_test_river_song',
            'stripe_session_id' => 'cs_test_river_song',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(ListPayments::class)
            ->mountTableAction('inspect', $payment)
            ->assertSee('River Song')
            ->assertSee($order->number)
            ->assertSee('pi_test_river_song');
    });
});
