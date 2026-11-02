<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Admin Profile Management', function () {
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

        $this->admin = AdminUser::factory()->create([
            'name' => 'Original Admin Name',
            'email' => 'admin.profile@greenveil.test',
            'password' => Hash::make('CurrentPassword123!'),
            'admin_role_id' => $this->role->id,
            'is_active' => true,
        ]);
    });

    it('renders the edit profile page for authenticated admin', function () {
        $this->actingAs($this->admin, 'admin')
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('Original Admin Name')
            ->assertSee('admin.profile@greenveil.test')
            ->assertSee('Super Admin');
    });

    it('allows admin to update their own name', function () {
        Livewire::actingAs($this->admin, 'admin')
            ->test(EditProfile::class)
            ->fillForm([
                'name' => 'Updated Admin Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($this->admin->fresh()->name)->toBe('Updated Admin Name');
    });

    it('allows admin to update their password with current password verification', function () {
        Livewire::actingAs($this->admin, 'admin')
            ->test(EditProfile::class)
            ->fillForm([
                'currentPassword' => 'CurrentPassword123!',
                'password' => 'NewSecurePassword456!',
                'passwordConfirmation' => 'NewSecurePassword456!',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->admin->refresh();
        expect(Hash::check('NewSecurePassword456!', $this->admin->password))->toBeTrue();
    });

    it('rejects password update if current password is incorrect', function () {
        Livewire::actingAs($this->admin, 'admin')
            ->test(EditProfile::class)
            ->fillForm([
                'currentPassword' => 'WrongCurrentPassword',
                'password' => 'NewSecurePassword456!',
                'passwordConfirmation' => 'NewSecurePassword456!',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->admin->refresh();
        expect(Hash::check('CurrentPassword123!', $this->admin->password))->toBeTrue();
    });
});
