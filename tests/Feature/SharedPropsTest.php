<?php

use App\Models\Category;
use App\Models\User;

test('shared inertia props include navigation, cart, favourites and flash', function () {
    Category::factory()->create([
        'name' => 'Skincare',
        'slug' => 'skincare',
        'is_active' => true,
        'parent_id' => null,
    ]);

    $response = $this->withSession([
        'cart' => [
            ['id' => 1, 'price' => 15.00, 'quantity' => 2],
        ],
        'favourites' => [1, 2],
        'success' => 'Item added',
    ])->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('navigation')
        ->where('cart.count', 2)
        ->where('cart.subtotal', 30)
        ->where('favourites', [1, 2])
        ->where('flash.success', 'Item added')
        ->where('auth.user', null)
    );
});

test('shared auth user does not expose sensitive admin or password fields', function () {
    $user = User::factory()->create([
        'name' => 'Test Admin',
        'email' => 'admin_test@example.com',
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('auth.user.name', 'Test Admin')
        ->where('auth.user.email', 'admin_test@example.com')
        ->missing('auth.user.password')
        ->missing('auth.user.is_admin')
    );
});

test('shared demo mode props reflect configuration', function () {
    config(['app.demo_mode' => true, 'app.demo_notice' => 'Custom demo notice for testing']);

    $response = $this->get('/');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('demo_mode', true)
        ->where('demo_notice', 'Custom demo notice for testing')
    );

    config(['app.demo_mode' => false]);
    $responseInactive = $this->get('/');
    $responseInactive->assertOk();
    $responseInactive->assertInertia(fn ($page) => $page
        ->where('demo_mode', false)
    );
});
