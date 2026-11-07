<?php

use App\Filament\Widgets\InventoryAlertsTable;
use App\Filament\Widgets\LatestOrdersTable;
use App\Filament\Widgets\OrdersStatsOverview;
use App\Models\AdminRole;
use App\Models\AdminUser;

describe('Category-Level Role-Based Access Control', function () {
    beforeEach(function () {
        $this->superAdminRole = AdminRole::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Permanent system role with full administrative privileges.',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        $this->catalogRole = AdminRole::firstOrCreate(
            ['slug' => 'catalog_manager'],
            [
                'name' => 'Catalog Manager',
                'description' => 'Manages catalog and marketing',
                'permissions' => ['shop', 'marketing'],
                'is_system' => false,
            ]
        );

        $this->fulfillmentRole = AdminRole::firstOrCreate(
            ['slug' => 'order_fulfillment'],
            [
                'name' => 'Order Fulfillment',
                'description' => 'Fulfills incoming orders',
                'permissions' => ['orders'],
                'is_system' => false,
            ]
        );
    });

    it('grants Super Admin unrestricted access across all categories', function () {
        $superAdmin = AdminUser::factory()->create([
            'admin_role_id' => $this->superAdminRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin, 'admin');

        $this->get('/admin/products')->assertOk();
        $this->get('/admin/categories')->assertOk();
        $this->get('/admin/promotions')->assertOk();
        $this->get('/admin/orders')->assertOk();
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/payments')->assertOk();
        $this->get('/admin/slider-items')->assertOk();
        $this->get('/admin/delivery-options')->assertOk();
        $this->get('/admin/payment-options')->assertOk();
        $this->get('/admin/admin-users')->assertOk();
        $this->get('/admin/admin-roles')->assertOk();
        $this->get('/admin/stripe-events')->assertOk();
    });

    it('allows Catalog Manager to view products and marketing but restricts orders and system', function () {
        $catalogManager = AdminUser::factory()->create([
            'admin_role_id' => $this->catalogRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($catalogManager, 'admin');

        // Permitted categories (shop, marketing)
        $this->get('/admin/products')->assertOk();
        $this->get('/admin/categories')->assertOk();
        $this->get('/admin/promotions')->assertOk();
        $this->get('/admin/slider-items')->assertOk();

        // Forbidden categories (orders, customers, finance, settings, system)
        $this->get('/admin/orders')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/payments')->assertForbidden();
        $this->get('/admin/delivery-options')->assertForbidden();
        $this->get('/admin/payment-options')->assertForbidden();
        $this->get('/admin/admin-users')->assertForbidden();
        $this->get('/admin/admin-roles')->assertForbidden();
        $this->get('/admin/stripe-events')->assertForbidden();

        // Widget permissions
        expect(InventoryAlertsTable::canView())->toBeTrue();
        expect(OrdersStatsOverview::canView())->toBeFalse();
        expect(LatestOrdersTable::canView())->toBeFalse();
    });

    it('allows Order Fulfillment to view orders but restricts catalog and settings', function () {
        $fulfillmentStaff = AdminUser::factory()->create([
            'admin_role_id' => $this->fulfillmentRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($fulfillmentStaff, 'admin');

        // Permitted category (orders)
        $this->get('/admin/orders')->assertOk();

        // Forbidden categories
        $this->get('/admin/products')->assertForbidden();
        $this->get('/admin/categories')->assertForbidden();
        $this->get('/admin/delivery-options')->assertForbidden();
        $this->get('/admin/admin-users')->assertForbidden();

        // Widget permissions
        expect(LatestOrdersTable::canView())->toBeTrue();
        expect(OrdersStatsOverview::canView())->toBeTrue();
        expect(InventoryAlertsTable::canView())->toBeFalse();
    });
});
