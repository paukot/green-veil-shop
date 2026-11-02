<?php

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\User;
use App\Policies\AdminRolePolicy;
use App\Policies\AdminUserPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\DeliveryOptionPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentOptionPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\StripeEventPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Policy Permission Pattern (user->can && user->hasCategoryAccess)', function () {
    beforeEach(function () {
        $this->superRole = AdminRole::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Super Admin', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->ordersRole = AdminRole::firstOrCreate(
            ['slug' => 'orders_clerk'],
            ['name' => 'Orders Clerk', 'permissions' => ['orders'], 'is_system' => false]
        );

        $this->shopRole = AdminRole::firstOrCreate(
            ['slug' => 'shop_manager'],
            ['name' => 'Shop Manager', 'permissions' => ['shop'], 'is_system' => false]
        );

        $this->systemRole = AdminRole::firstOrCreate(
            ['slug' => 'system_operator'],
            ['name' => 'System Operator', 'permissions' => ['system'], 'is_system' => false]
        );
    });

    it('denies storefront users from accessing admin model policies', function () {
        $storefrontUser = User::factory()->create();

        $orderPolicy = new OrderPolicy;
        $productPolicy = new ProductPolicy;
        $userPolicy = new UserPolicy;

        expect($storefrontUser->hasCategoryAccess('orders'))->toBeFalse();
        expect($orderPolicy->viewAny($storefrontUser))->toBeFalse();
        expect($productPolicy->viewAny($storefrontUser))->toBeFalse();
        expect($userPolicy->viewAny($storefrontUser))->toBeFalse();
    });

    it('validates OrderPolicy pattern with admin user category permissions', function () {
        $orderClerk = AdminUser::factory()->create(['admin_role_id' => $this->ordersRole->id]);
        $shopManager = AdminUser::factory()->create(['admin_role_id' => $this->shopRole->id]);

        $policy = new OrderPolicy;

        // Clerk with 'orders' category
        expect($orderClerk->can('view_any_order'))->toBeTrue();
        expect($orderClerk->hasCategoryAccess('orders'))->toBeTrue();
        expect($policy->viewAny($orderClerk))->toBeTrue();

        // Shop manager without 'orders' category
        expect($policy->viewAny($shopManager))->toBeFalse();
    });

    it('validates ProductPolicy, CategoryPolicy, PromotionPolicy for shop category', function () {
        $shopManager = AdminUser::factory()->create(['admin_role_id' => $this->shopRole->id]);
        $orderClerk = AdminUser::factory()->create(['admin_role_id' => $this->ordersRole->id]);

        $productPolicy = new ProductPolicy;
        $categoryPolicy = new CategoryPolicy;
        $promotionPolicy = new PromotionPolicy;

        expect($productPolicy->viewAny($shopManager))->toBeTrue();
        expect($categoryPolicy->viewAny($shopManager))->toBeTrue();
        expect($promotionPolicy->viewAny($shopManager))->toBeTrue();

        expect($productPolicy->viewAny($orderClerk))->toBeFalse();
        expect($categoryPolicy->viewAny($orderClerk))->toBeFalse();
        expect($promotionPolicy->viewAny($orderClerk))->toBeFalse();
    });

    it('validates Settings policies (DeliveryOptionPolicy, PaymentOptionPolicy)', function () {
        $settingsRole = AdminRole::create([
            'name' => 'Settings Admin',
            'slug' => 'settings_admin',
            'permissions' => ['settings'],
            'is_system' => false,
        ]);
        $settingsAdmin = AdminUser::factory()->create(['admin_role_id' => $settingsRole->id]);
        $shopManager = AdminUser::factory()->create(['admin_role_id' => $this->shopRole->id]);

        $deliveryPolicy = new DeliveryOptionPolicy;
        $paymentOptionPolicy = new PaymentOptionPolicy;

        expect($deliveryPolicy->viewAny($settingsAdmin))->toBeTrue();
        expect($paymentOptionPolicy->viewAny($settingsAdmin))->toBeTrue();

        expect($deliveryPolicy->viewAny($shopManager))->toBeFalse();
        expect($paymentOptionPolicy->viewAny($shopManager))->toBeFalse();
    });

    it('validates AdminRolePolicy prevents deleting system roles', function () {
        $systemAdmin = AdminUser::factory()->create(['admin_role_id' => $this->systemRole->id]);
        $policy = new AdminRolePolicy;

        expect($policy->viewAny($systemAdmin))->toBeTrue();
        expect($policy->delete($systemAdmin, $this->superRole))->toBeFalse();
        expect($policy->delete($systemAdmin, $this->ordersRole))->toBeTrue();
    });

    it('validates AdminUserPolicy prevents self-deletion and last super admin deletion', function () {
        $superAdmin = AdminUser::factory()->create(['admin_role_id' => $this->superRole->id]);
        $otherSuperAdmin = AdminUser::factory()->create(['admin_role_id' => $this->superRole->id]);
        $systemAdmin = AdminUser::factory()->create(['admin_role_id' => $this->systemRole->id]);

        $policy = new AdminUserPolicy;

        // Cannot delete self
        expect($policy->delete($superAdmin, $superAdmin))->toBeFalse();

        // With 2 super admins, deleting other is permitted
        expect($policy->delete($superAdmin, $otherSuperAdmin))->toBeTrue();

        // If only 1 super admin remains, deletion is denied
        $otherSuperAdmin->delete();
        expect($policy->delete($systemAdmin, $superAdmin))->toBeFalse();
    });

    it('validates StripeEventPolicy is read-only', function () {
        $systemAdmin = AdminUser::factory()->create(['admin_role_id' => $this->systemRole->id]);
        $orderClerk = AdminUser::factory()->create(['admin_role_id' => $this->ordersRole->id]);

        $policy = new StripeEventPolicy;

        expect($policy->viewAny($systemAdmin))->toBeTrue();
        expect($policy->viewAny($orderClerk))->toBeFalse();
        expect($policy->create($systemAdmin))->toBeFalse();
    });
});
