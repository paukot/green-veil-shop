<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
        );
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'email' => 'customer@greenveil.test',
        'password' => bcrypt('botanical123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'customer@greenveil.test',
        'password' => 'botanical123',
    ]);

    $this->assertAuthenticated();
    $this->assertEquals($user->id, Auth::id());
    $response->assertRedirect(route('home'));
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create([
        'email' => 'customer@greenveil.test',
        'password' => bcrypt('botanical123'),
    ]);

    $this->post('/login', [
        'email' => 'customer@greenveil.test',
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('login attempts are rate limited after 5 failed attempts', function () {
    $user = User::factory()->create([
        'email' => 'throttled@greenveil.test',
        'password' => bcrypt('botanical123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'throttled@greenveil.test',
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'email' => 'throttled@greenveil.test',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $errors = session('errors')->get('email');
    $this->assertStringContainsString('Too many login attempts', $errors[0]);
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Register')
        );
});

test('new customers can register as storefront users', function () {
    $response = $this->post('/register', [
        'name' => 'Rosemary Green',
        'email' => 'rosemary@greenveil.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->assertAuthenticated('web');
    $response->assertRedirect(route('home'));

    $user = User::where('email', 'rosemary@greenveil.test')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Rosemary Green');
});

test('newly registered customer is strictly blocked from the filament admin panel', function () {
    $this->post('/register', [
        'name' => 'Lavender Bloom',
        'email' => 'lavender@greenveil.test',
        'password' => 'secret12345',
        'password_confirmation' => 'secret12345',
    ]);

    $this->assertAuthenticated('web');

    $response = $this->get('/admin');
    $response->assertRedirect('/admin/login');
});

test('authenticated users can log out', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});
