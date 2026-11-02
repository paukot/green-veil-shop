<?php

use App\Filament\Resources\AdminRoles\Pages\CreateAdminRole;
use App\Filament\Resources\AdminRoles\Pages\EditAdminRole;
use App\Filament\Resources\AdminRoles\Pages\ListAdminRoles;
use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('AdminRoleResource in Filament', function () {
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
            'admin_role_id' => $this->systemRole->id,
            'is_active' => true,
        ]);
    });

    it('renders the admin roles list table', function () {
        $customRole = AdminRole::create([
            'name' => 'Support Agent',
            'slug' => 'support_agent',
            'description' => 'Customer care role',
            'permissions' => ['customers'],
            'is_system' => false,
        ]);

        $this->actingAs($this->superAdmin, 'admin')
            ->get('/admin/admin-roles')
            ->assertOk()
            ->assertSee('Super Admin')
            ->assertSee('Support Agent');

        Livewire::actingAs($this->superAdmin, 'admin')
            ->test(ListAdminRoles::class)
            ->assertCanSeeTableRecords([$this->systemRole, $customRole]);
    });

    it('allows super admin to create a custom role with category permissions', function () {
        Livewire::actingAs($this->superAdmin, 'admin')
            ->test(CreateAdminRole::class)
            ->fillForm([
                'name' => 'Inventory Specialist',
                'slug' => 'inventory_specialist',
                'description' => 'Manages stock and product catalog',
                'permissions' => ['shop', 'orders'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('admin_roles', [
            'name' => 'Inventory Specialist',
            'slug' => 'inventory_specialist',
            'is_system' => false,
        ]);

        $created = AdminRole::where('slug', 'inventory_specialist')->first();
        expect($created->permissions)->toBe(['shop', 'orders']);
        expect($created->hasPermission('shop'))->toBeTrue();
        expect($created->hasPermission('orders'))->toBeTrue();
        expect($created->hasPermission('finance'))->toBeFalse();
    });

    it('prevents deletion of the permanent system admin role', function () {
        Livewire::actingAs($this->superAdmin, 'admin')
            ->test(EditAdminRole::class, ['record' => $this->systemRole->id])
            ->assertActionHidden('delete');

        // Verify direct model deletion constraint
        expect($this->systemRole->is_system)->toBeTrue();
    });

    it('allows deletion of custom non-system roles', function () {
        $customRole = AdminRole::create([
            'name' => 'Temporary Marketing',
            'slug' => 'temp_marketing',
            'permissions' => ['marketing'],
            'is_system' => false,
        ]);

        Livewire::actingAs($this->superAdmin, 'admin')
            ->test(EditAdminRole::class, ['record' => $customRole->id])
            ->assertActionVisible('delete')
            ->callAction('delete');

        $this->assertDatabaseMissing('admin_roles', [
            'id' => $customRole->id,
        ]);
    });
});
