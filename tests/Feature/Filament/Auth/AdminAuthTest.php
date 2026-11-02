<?php

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Admin Authentication', function () {
    beforeEach(function () {
        $this->role = AdminRole::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Permanent system role with full administrative privileges.',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );
    });

    it('renders the admin login page', function () {
        $response = $this->get('/admin/login');

        $response->assertOk();
    });

    it('allows active admin user to authenticate with valid credentials', function () {
        $admin = AdminUser::factory()->create([
            'email' => 'staff@greenveil.test',
            'password' => Hash::make('securePassword123'),
            'admin_role_id' => $this->role->id,
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'staff@greenveil.test',
                'password' => 'securePassword123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin, 'admin');
    });

    it('rejects login with incorrect password', function () {
        AdminUser::factory()->create([
            'email' => 'staff@greenveil.test',
            'password' => Hash::make('correctPassword'),
            'admin_role_id' => $this->role->id,
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'staff@greenveil.test',
                'password' => 'wrongPassword',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest('admin');
    });

    it('rejects storefront customer credentials on admin login page', function () {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('customerPassword123'),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'customer@example.com',
                'password' => 'customerPassword123',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest('admin');
    });

    it('rejects deactivated admin user from logging in', function () {
        AdminUser::factory()->create([
            'email' => 'deactivated@greenveil.test',
            'password' => Hash::make('anyPassword123'),
            'admin_role_id' => $this->role->id,
            'is_active' => false,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'deactivated@greenveil.test',
                'password' => 'anyPassword123',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest('admin');
    });

    it('allows authenticated admin to log out', function () {
        $admin = AdminUser::factory()->superAdmin()->create();

        $response = $this->actingAs($admin, 'admin')->post('/admin/logout');

        $this->assertGuest('admin');
        $response->assertRedirect('/admin/login');
    });
});
