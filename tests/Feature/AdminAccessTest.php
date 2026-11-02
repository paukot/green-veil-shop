<?php

use App\Models\AdminUser;
use App\Models\User;

test('unauthenticated users are redirected to admin login', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});

test('storefront customers cannot access the filament admin panel', function () {
    $customer = User::factory()->create();

    $response = $this->actingAs($customer, 'web')->get('/admin');

    // Customer is authenticated on web guard, but unauthenticated on admin guard
    $response->assertRedirect('/admin/login');
});

test('administrators can access the filament admin panel', function () {
    $admin = AdminUser::factory()->superAdmin()->create();

    $response = $this->actingAs($admin, 'admin')->get('/admin');

    $response->assertOk();
});

test('deactivated administrators are rejected from the admin panel', function () {
    $admin = AdminUser::factory()->superAdmin()->inactive()->create();

    $response = $this->actingAs($admin, 'admin')->get('/admin');

    $response->assertForbidden();
});
