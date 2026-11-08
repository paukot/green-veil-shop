<?php

use App\Filament\Resources\Countries\Pages\CreateCountry;
use App\Filament\Resources\Countries\Pages\EditCountry;
use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Country;
use App\Models\User;
use Livewire\Livewire;

describe('Filament Country Management', function () {
    beforeEach(function () {
        $this->admin = AdminUser::factory()->superAdmin()->create();
    });

    it('denies access to non-admins', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->get('/admin/countries')
            ->assertRedirect('/admin/login');
    });

    it('denies access to admins without settings/country permissions', function () {
        $role = AdminRole::factory()->create([
            'slug' => 'restricted-admin',
            'permissions' => ['view_dashboard'],
        ]);

        $restrictedAdmin = AdminUser::factory()->withRole($role)->create([
            'is_active' => true,
        ]);

        $this->actingAs($restrictedAdmin, 'admin')
            ->get('/admin/countries')
            ->assertForbidden();
    });

    it('renders list page with countries and supports searching', function () {
        $ireland = Country::where('code', 'IE')->first();
        $france = Country::where('code', 'FR')->first();

        $this->actingAs($this->admin, 'admin')
            ->get('/admin/countries')
            ->assertOk()
            ->assertSee('Ireland')
            ->assertSee('France');

        Livewire::actingAs($this->admin, 'admin')
            ->test(ListCountries::class)
            ->assertCanSeeTableRecords([$ireland, $france])
            ->searchTable('Ireland')
            ->assertCanSeeTableRecords([$ireland])
            ->assertCanNotSeeTableRecords([$france]);
    });

    it('allows admin to create a new country with valid attributes', function () {
        Livewire::actingAs($this->admin, 'admin')
            ->test(CreateCountry::class)
            ->fillForm([
                'code' => 'nz',
                'name' => 'New Zealand',
                'is_active' => true,
                'is_shipping_available' => true,
                'sort_order' => 50,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('countries', [
            'code' => 'NZ',
            'name' => 'New Zealand',
            'is_active' => true,
            'is_shipping_available' => true,
            'sort_order' => 50,
        ]);

        expect(Country::getOptionList())->toHaveKey('NZ', 'New Zealand');
    });

    it('validates uniqueness and ISO format of country code on creation', function () {
        Livewire::actingAs($this->admin, 'admin')
            ->test(CreateCountry::class)
            ->fillForm([
                'code' => 'IE', // already exists
                'name' => 'Duplicate Ireland',
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique']);

        Livewire::actingAs($this->admin, 'admin')
            ->test(CreateCountry::class)
            ->fillForm([
                'code' => 'TOOLONG',
                'name' => 'Invalid Code Country',
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'size']);
    });

    it('allows admin to update an existing country', function () {
        $country = Country::where('code', 'SE')->firstOrFail();

        Livewire::actingAs($this->admin, 'admin')
            ->test(EditCountry::class, [
                'record' => $country->getRouteKey(),
            ])
            ->fillForm([
                'name' => 'Kingdom of Sweden',
                'is_shipping_available' => true,
                'sort_order' => 15,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $country->refresh();
        expect($country->name)->toBe('Kingdom of Sweden');
        expect($country->is_shipping_available)->toBeTrue();
        expect($country->sort_order)->toBe(15);
    });

    it('clears and updates cached country options on create, update, and delete', function () {
        Country::clearCountryCache();
        $initialShipping = Country::getShippingOptions();
        expect(collect($initialShipping)->pluck('value'))->not->toContain('IS');

        $iceland = Country::create([
            'code' => 'IS',
            'name' => 'Iceland',
            'is_active' => true,
            'is_shipping_available' => true,
            'sort_order' => 8,
        ]);

        $updatedShipping = Country::getShippingOptions();
        expect(collect($updatedShipping)->pluck('value'))->toContain('IS');

        $iceland->delete();

        $afterDeleteShipping = Country::getShippingOptions();
        expect(collect($afterDeleteShipping)->pluck('value'))->not->toContain('IS');
    });
});
