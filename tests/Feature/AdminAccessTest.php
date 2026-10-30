<?php

use App\Models\User;

test('unauthenticated users are redirected to admin login', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});

test('customers cannot access the filament admin panel and receive 403', function () {
    $customer = User::factory()->create([
        'is_admin' => false,
    ]);

    $response = $this->actingAs($customer)->get('/admin');

    $response->assertForbidden();
});

test('administrators can access the filament admin panel', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertOk();
});
