<?php

use App\Filament\Resources\PaymentOptions\Pages\CreatePaymentOption;
use App\Filament\Resources\PaymentOptions\Pages\EditPaymentOption;
use App\Filament\Resources\PaymentOptions\Pages\ListPaymentOptions;
use App\Models\PaymentOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('PaymentOptionResource in Filament', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
    });

    it('denies access to non-admins', function () {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/payment-options')
            ->assertForbidden();
    });

    it('renders list page with payment options', function () {
        $stripeOption = PaymentOption::factory()->stripe()->create();
        $bankOption = PaymentOption::factory()->bankTransfer()->create();

        $this->actingAs($this->admin)
            ->get('/admin/payment-options')
            ->assertOk()
            ->assertSee($stripeOption->name)
            ->assertSee($bankOption->name);

        Livewire::actingAs($this->admin)
            ->test(ListPaymentOptions::class)
            ->assertCanSeeTableRecords([$stripeOption, $bankOption]);
    });

    it('allows admin to create a new payment option', function () {
        Livewire::actingAs($this->admin)
            ->test(CreatePaymentOption::class)
            ->fillForm([
                'name' => 'Cash On Delivery',
                'code' => 'cod_test',
                'driver' => 'cash_on_delivery',
                'description' => 'Pay when order arrives at your door',
                'instructions' => 'Have exact cash ready.',
                'is_active' => true,
                'sort_order' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('payment_options', [
            'code' => 'cod_test',
            'name' => 'Cash On Delivery',
            'driver' => 'cash_on_delivery',
            'is_active' => true,
        ]);
    });

    it('allows admin to update an existing payment option', function () {
        $option = PaymentOption::factory()->bankTransfer()->create([
            'instructions' => 'Old bank details',
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditPaymentOption::class, ['record' => $option->id])
            ->fillForm([
                'instructions' => 'IBAN: IE99NEWBANK12345678',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($option->fresh()->instructions)->toBe('IBAN: IE99NEWBANK12345678');
    });
});
