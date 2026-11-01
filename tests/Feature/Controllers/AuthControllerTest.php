<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('AuthController', function () {
    describe('createLogin', function () {
        it('renders login page for guests', function () {
            $response = $this->get(route('login'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
            );
        });

        it('redirects authenticated users away from login page', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->get(route('login'));

            $response->assertRedirect(route('home'));
        });
    });

    describe('storeLogin', function () {
        it('validates email and password requirements on login', function () {
            $response = $this->post('/login', []);

            $response->assertSessionHasErrors(['email', 'password']);
        });

        it('authenticates user with valid credentials, regenerates session, and redirects', function () {
            $user = User::factory()->create([
                'email' => 'botanical.lover@example.com',
                'password' => Hash::make('Secret123!'),
            ]);

            $response = $this->post('/login', [
                'email' => 'botanical.lover@example.com',
                'password' => 'Secret123!',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect(route('home'));
            $this->assertAuthenticatedAs($user);
        });

        it('rejects invalid credentials with validation error on email', function () {
            $user = User::factory()->create([
                'email' => 'botanical.lover@example.com',
                'password' => Hash::make('Secret123!'),
            ]);

            $response = $this->post('/login', [
                'email' => 'botanical.lover@example.com',
                'password' => 'WrongPassword!',
            ]);

            $response->assertSessionHasErrors(['email']);
            $this->assertGuest();
        });

        it('merges guest cart and guest favourites into user account on login', function () {
            $user = User::factory()->create([
                'email' => 'merger@example.com',
                'password' => Hash::make('Password123!'),
            ]);

            $productCart = Product::factory()->inStock(10)->create();
            $productFav = Product::factory()->create();

            $sessionData = [
                'cart' => [
                    $productCart->id => [
                        'id' => $productCart->id,
                        'product_id' => $productCart->id,
                        'quantity' => 3,
                    ],
                ],
                'favourites' => [$productFav->id],
            ];

            $response = $this->withSession($sessionData)->post('/login', [
                'email' => 'merger@example.com',
                'password' => 'Password123!',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect(route('home'));
            $this->assertAuthenticatedAs($user);

            // Verify cart merged into database
            $this->assertDatabaseHas('cart_items', [
                'user_id' => $user->id,
                'product_id' => $productCart->id,
                'quantity' => 3,
            ]);

            // Verify favourites merged into database
            $this->assertDatabaseHas('favourites', [
                'user_id' => $user->id,
                'product_id' => $productFav->id,
            ]);

            // Verify session keys forgotten
            $response->assertSessionMissing('cart');
            $response->assertSessionMissing('favourites');
        });
    });

    describe('createRegister', function () {
        it('renders register page for guests', function () {
            $response = $this->get(route('register'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
            );
        });

        it('redirects authenticated users away from register page', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->get(route('register'));

            $response->assertRedirect(route('home'));
        });
    });

    describe('storeRegister', function () {
        it('validates registration input requirements', function () {
            $existingUser = User::factory()->create(['email' => 'taken@example.com']);

            // Test missing fields
            $responseEmpty = $this->post('/register', []);
            $responseEmpty->assertSessionHasErrors(['name', 'email', 'password']);

            // Test duplicate email, short password, mismatched confirmation
            $responseInvalid = $this->post('/register', [
                'name' => 'John Doe',
                'email' => 'taken@example.com',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ]);
            $responseInvalid->assertSessionHasErrors(['email', 'password']);
        });

        it('creates new user, hashes password, logs in, and merges guest items', function () {
            $product = Product::factory()->inStock(10)->create();
            $sessionData = [
                'cart' => [
                    $product->id => [
                        'id' => $product->id,
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ];

            $response = $this->withSession($sessionData)->post('/register', [
                'name' => 'Fiona Gallagher',
                'email' => 'fiona.botanicals@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect(route('home'));

            $user = User::where('email', 'fiona.botanicals@example.com')->first();
            expect($user)->not->toBeNull()
                ->and(Hash::check('SecurePass123!', $user->password))->toBeTrue();

            $this->assertAuthenticatedAs($user);

            // Verify guest cart merged for newly registered user
            $this->assertDatabaseHas('cart_items', [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => 2,
            ]);
        });
    });

    describe('destroy', function () {
        it('logs user out, invalidates session, regenerates CSRF token, and redirects to home', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->post(route('logout'));

            $response->assertRedirect(route('home'));
            $this->assertGuest();
        });

        it('requires authentication to logout', function () {
            $response = $this->post(route('logout'));

            $response->assertRedirect(route('login'));
        });
    });
});
