<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Permanent Super Admin Role (always exists, all permissions)
        $superAdminRole = AdminRole::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Permanent system role with full administrative privileges across all categories.',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        // 2. Standard Functional Roles
        AdminRole::updateOrCreate(
            ['slug' => 'catalog_manager'],
            [
                'name' => 'Catalog Manager',
                'description' => 'Manages products, categories, and promotions.',
                'permissions' => ['shop', 'marketing'],
                'is_system' => false,
            ]
        );

        AdminRole::updateOrCreate(
            ['slug' => 'order_fulfillment'],
            [
                'name' => 'Order Fulfillment',
                'description' => 'Manages orders, shipping, and delivery fulfillment.',
                'permissions' => ['orders'],
                'is_system' => false,
            ]
        );

        AdminRole::updateOrCreate(
            ['slug' => 'customer_support'],
            [
                'name' => 'Customer Support',
                'description' => 'Accesses customer accounts, order history, and shopping carts.',
                'permissions' => ['customers', 'orders'],
                'is_system' => false,
            ]
        );

        AdminRole::updateOrCreate(
            ['slug' => 'finance'],
            [
                'name' => 'Finance & Bookkeeping',
                'description' => 'Accesses transaction logs, payment records, and financial reports.',
                'permissions' => ['finance', 'orders'],
                'is_system' => false,
            ]
        );

        // 3. Ensure Default Super Admin Account exists
        AdminUser::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'admin_role_id' => $superAdminRole->id,
                'is_active' => true,
            ]
        );
    }
}
