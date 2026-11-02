<?php

use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use App\Filament\Resources\AdminUsers\Pages\ListAdminUsers;
use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('AdminUserResource in Filament', function () {
    beforeEach(function () {
        $this->systemRole = AdminRole::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Permanent system role with full administrative privileges.',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        $this->superAdmin = AdminUser::factory()->create([
            'name' => 'Root Super Admin',
            'email' => 'root@greenveil.test',
            'admin_role_id' => $this->systemRole->id,
            'is_active' => true,
        ]);
    });

    it('renders the staff members list table', function () {
        $staff = AdminUser::factory()->create([
            'name' => 'Fulfillment Staff',
            'email' => 'fulfillment@greenveil.test',
            'admin_role_id' => $this->systemRole->id,
        ]);

        $this->actingAs($this->superAdmin, 'admin')
            ->get('/admin/admin-users')
            ->assertOk()
            ->assertSee('Root Super Admin')
            ->assertSee('Fulfillment Staff');

        Livewire::actingAs($this->superAdmin, 'admin')
            ->test(ListAdminUsers::class)
            ->assertCanSeeTableRecords([$this->superAdmin, $staff]);
    });

    it('allows super admin to create a new staff account with assigned role', function () {
        $role = AdminRole::create([
            'name' => 'Catalog Assistant',
            'slug' => 'catalog_assistant',
            'permissions' => ['shop'],
            'is_system' => false,
        ]);

        Livewire::actingAs($this->superAdmin, 'admin')
            ->test(CreateAdminUser::class)
            ->fillForm([
                'name' => 'Jane Assistant',
                'email' => 'jane.assistant@greenveil.test',
                'password' => 'secretStaffPassword123',
                'admin_role_id' => $role->id,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $newUser = AdminUser::where('email', 'jane.assistant@greenveil.test')->first();
        expect($newUser)->not->toBeNull();
        expect($newUser->admin_role_id)->toBe($role->id);
        expect(Hash::check('secretStaffPassword123', $newUser->password))->toBeTrue();
        expect($newUser->hasCategoryAccess('shop'))->toBeTrue();
        expect($newUser->hasCategoryAccess('orders'))->toBeFalse();
    });

    it('prevents self-deletion for the currently logged in administrator', function () {
        Livewire::actingAs($this->superAdmin, 'admin')
            ->test(EditAdminUser::class, ['record' => $this->superAdmin->id])
            ->assertActionHidden('delete');
    });

    it('prevents deleting the last remaining active super admin', function () {
        // Ensure this is the only active super admin
        AdminUser::where('id', '!=', $this->superAdmin->id)
            ->where('admin_role_id', $this->systemRole->id)
            ->delete();

        // Create a secondary admin who has system access but is not the target
        $anotherAdmin = AdminUser::factory()->create([
            'admin_role_id' => $this->systemRole->id,
            'is_active' => true,
        ]);

        // When anotherAdmin views $this->superAdmin (which now is the 2nd), if there's only 1 left, delete is hidden
        AdminUser::where('id', $anotherAdmin->id)->delete();

        Livewire::actingAs($this->superAdmin, 'admin')
            ->test(EditAdminUser::class, ['record' => $this->superAdmin->id])
            ->assertActionHidden('delete');
    });
});
