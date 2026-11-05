<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('registering a new user claims existing guest orders with matching email', function () {
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

test('logging in claims existing guest orders with matching email', function () {
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

    $response = $this->post('/login', [
        'email' => 'returning@example.com',
        'password' => 'SecretPass123!',
    ]);

    $response->assertRedirect(route('home'));

    $guestOrder->refresh();
    expect($guestOrder->user_id)->toBe($user->id);
});
