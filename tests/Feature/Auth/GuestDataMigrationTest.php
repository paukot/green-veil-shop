<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CartItem;
use App\Models\Favourite;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('Guest Data Migration', function () {
    describe('Session Cart & Favourites Migration', function () {
        it('merges guest cart items and favourites into customer account on login', function () {
            $productA = Product::factory()->create([
                'price' => 25.00,
                'stock_quantity' => 10,
                'track_inventory' => true,
                'is_active' => true,
            ]);

            $productB = Product::factory()->create([
                'price' => 45.00,
                'stock_quantity' => 5,
                'track_inventory' => true,
                'is_active' => true,
            ]);

            $user = User::factory()->create([
                'email' => 'merge-customer@greenveil.test',
                'password' => Hash::make('password123'),
            ]);

            $this->withSession([
                'cart' => [
                    $productA->id => [
                        'id' => $productA->id,
                        'product_id' => $productA->id,
                        'quantity' => 2,
                    ],
                ],
                'favourites' => [$productB->id],
            ]);

            $response = $this->post('/login', [
                'email' => 'merge-customer@greenveil.test',
                'password' => 'password123',
            ]);

            $this->assertAuthenticated();
            $response->assertRedirect(route('home'));

            expect(CartItem::where('user_id', $user->id)->where('product_id', $productA->id)->value('quantity'))->toBe(2);
            expect(Favourite::where('user_id', $user->id)->where('product_id', $productB->id)->exists())->toBeTrue();

            expect(session()->has('cart'))->toBeFalse();
            expect(session()->has('favourites'))->toBeFalse();
        });

        it('increments existing database cart item quantity when merging duplicate guest item', function () {
            $product = Product::factory()->create([
                'stock_quantity' => 10,
                'track_inventory' => true,
                'is_active' => true,
            ]);

            $user = User::factory()->create([
                'email' => 'merge-existing@greenveil.test',
                'password' => Hash::make('password123'),
            ]);

            CartItem::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => 3,
            ]);

            $this->withSession([
                'cart' => [
                    $product->id => [
                        'id' => $product->id,
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

            $this->post('/login', [
                'email' => 'merge-existing@greenveil.test',
                'password' => 'password123',
            ]);

            expect(CartItem::where('user_id', $user->id)->where('product_id', $product->id)->value('quantity'))->toBe(5);
        });

        it('merges guest cart and favourites on new customer registration', function () {
            $product = Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);

            $this->withSession([
                'cart' => [
                    $product->id => ['id' => $product->id, 'product_id' => $product->id, 'quantity' => 1],
                ],
                'favourites' => [$product->id],
            ]);

            $this->post('/register', [
                'name' => 'New Customer',
                'email' => 'registered-merge@greenveil.test',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

            $user = User::where('email', 'registered-merge@greenveil.test')->first();
            expect($user)->not->toBeNull();

            expect(CartItem::where('user_id', $user->id)->where('product_id', $product->id)->exists())->toBeTrue();
            expect(Favourite::where('user_id', $user->id)->where('product_id', $product->id)->exists())->toBeTrue();
        });
    });

    describe('Guest Order Claiming', function () {
        it('claims existing guest orders matching customer email on registration', function () {
            $guestOrder1 = Order::factory()->create([
                'user_id' => null,
                'customer_email' => 'newuser@example.com',
                'status' => OrderStatus::Processing,
                'payment_status' => PaymentStatus::Paid,
            ]);

            $guestOrder2 = Order::factory()->create([
                'user_id' => null,
                'customer_email' => 'newuser@example.com',
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Unpaid,
            ]);

            $otherGuestOrder = Order::factory()->create([
                'user_id' => null,
                'customer_email' => 'someoneelse@example.com',
            ]);

            $response = $this->post('/register', [
                'name' => 'New Customer',
                'email' => 'newuser@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
            ]);

            $response->assertRedirect(route('home'));

            $user = User::where('email', 'newuser@example.com')->first();
            expect($user)->not->toBeNull();

            $guestOrder1->refresh();
            $guestOrder2->refresh();
            $otherGuestOrder->refresh();

            expect($guestOrder1->user_id)->toBe($user->id);
            expect($guestOrder2->user_id)->toBe($user->id);
            expect($otherGuestOrder->user_id)->toBeNull();
        });

        it('claims existing guest orders matching customer email on login', function () {
            $user = User::factory()->create([
                'email' => 'returning@example.com',
                'password' => Hash::make('SecretPass123!'),
            ]);

            $guestOrder = Order::factory()->create([
                'user_id' => null,
                'customer_email' => 'returning@example.com',
                'status' => OrderStatus::Processing,
                'payment_status' => PaymentStatus::Paid,
            ]);

            $this->post('/login', [
                'email' => 'returning@example.com',
                'password' => 'SecretPass123!',
            ]);

            $guestOrder->refresh();
            expect($guestOrder->user_id)->toBe($user->id);
        });
    });
});
