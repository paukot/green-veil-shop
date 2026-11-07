<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

describe('Customer Authentication', function () {
    describe('Login Views & Rules', function () {
        it('renders login screen for guests', function () {
            $response = $this->get(route('login'));

            $response->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Auth/Login')
                );
        });

        it('redirects authenticated customers away from login page', function () {
            $user = User::factory()->create();

            $response = actingAsCustomer($user)->get(route('login'));

            $response->assertRedirect(route('home'));
        });
    });

    describe('Login Authentication', function () {
        it('validates email and password requirements on login', function () {
            $response = $this->post('/login', []);

            $response->assertSessionHasErrors(['email', 'password']);
        });

        it('authenticates user with valid credentials, regenerates session, and redirects home', function () {
            $user = User::factory()->create([
                'email' => 'customer@greenveil.test',
                'password' => Hash::make('botanical123'),
            ]);

            $response = $this->post('/login', [
                'email' => 'customer@greenveil.test',
                'password' => 'botanical123',
            ]);

            $this->assertAuthenticated();
            expect(Auth::id())->toBe($user->id);
            $response->assertRedirect(route('home'));
        });

        it('rejects invalid credentials with validation error on email', function () {
            User::factory()->create([
                'email' => 'customer@greenveil.test',
                'password' => Hash::make('botanical123'),
            ]);

            $response = $this->post('/login', [
                'email' => 'customer@greenveil.test',
                'password' => 'wrong-password',
            ]);

            $this->assertGuest();
            $response->assertSessionHasErrors('email');
        });

        it('rate limits login attempts after 5 failed attempts', function () {
            $email = 'throttled@greenveil.test';
            User::factory()->create([
                'email' => $email,
                'password' => Hash::make('botanical123'),
            ]);

            $throttleKey = Str::transliterate(Str::lower($email).'|127.0.0.1');
            for ($i = 0; $i < 5; $i++) {
                RateLimiter::hit($throttleKey);
            }

            $response = $this->post('/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ]);

            $response->assertSessionHasErrors('email');
            $errors = session('errors')->get('email');
            expect($errors[0])->toContain('Too many login attempts');
        });
    });

    describe('Registration', function () {
        it('renders registration screen for guests', function () {
            $response = $this->get(route('register'));

            $response->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Auth/Register')
                );
        });

        it('redirects authenticated customers away from registration page', function () {
            $user = User::factory()->create();

            $response = actingAsCustomer($user)->get(route('register'));

            $response->assertRedirect(route('home'));
        });

        it('validates registration input fields', function () {
            $response = $this->post('/register', []);

            $response->assertSessionHasErrors(['name', 'email', 'password']);
        });

        it('registers new customers and authenticates them as storefront users', function () {
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

        it('strictly blocks newly registered customers from the filament admin panel', function () {
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
    });

    describe('Logout', function () {
        it('logs authenticated customers out and redirects to home', function () {
            $user = User::factory()->create();

            $response = actingAsCustomer($user)->post(route('logout'));

            $this->assertGuest();
            $response->assertRedirect(route('home'));
        });
    });
});
