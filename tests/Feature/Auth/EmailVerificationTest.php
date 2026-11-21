<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

function verificationUrlFor(User $user): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);
}

it('shows the verification notice to an unverified customer', function () {
    $user = User::factory()->unverified()->create(['email' => 'unverified@example.com']);

    $response = actingAsCustomer($user)->get(route('verification.notice'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Auth/VerifyEmail')
        ->where('email', 'unverified@example.com')
    );
});

it('redirects a verified customer away from the verification notice', function () {
    $response = actingAsCustomer()->get(route('verification.notice'));

    $response->assertRedirect(route('account.profile'));
});

it('resends the verification link', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $response = actingAsCustomer($user)->post(route('verification.send'));

    $response->assertRedirect();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('rejects a verification link signed for another address', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1('someone-else@example.com'),
    ]);

    $response = actingAsCustomer($user)->get($url);

    $response->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('claims guest orders for the verified email', function () {
    $user = User::factory()->unverified()->create(['email' => 'claim@example.com']);
    $guestOrder = Order::factory()->guest()->create(['customer_email' => 'claim@example.com']);

    $response = actingAsCustomer($user)->get(verificationUrlFor($user));

    $response->assertRedirect(route('account.profile'));
    expect($guestOrder->fresh()->user_id)->toBe($user->id);
});

it('does not claim guest orders when an unverified customer signs in', function () {
    $user = User::factory()->unverified()->create(['email' => 'squatter@example.com', 'password' => 'Password123!']);
    $guestOrder = Order::factory()->guest()->create(['customer_email' => 'squatter@example.com']);

    $this->post(route('login'), ['email' => 'squatter@example.com', 'password' => 'Password123!']);

    $this->assertAuthenticatedAs($user);
    expect($guestOrder->fresh()->user_id)->toBeNull();
});

it('sends a new verification link when the customer changes their email', function () {
    Notification::fake();
    $user = User::factory()->create();

    actingAsCustomer($user)->patch(route('account.profile.update'), [
        'name' => $user->name,
        'email' => 'new-address@example.com',
    ]);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});
