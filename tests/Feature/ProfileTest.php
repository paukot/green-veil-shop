<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login when attempting to visit account profile', function () {
    $response = $this->get('/account/profile');

    $response->assertRedirect('/login');
});

test('customers can view their account profile page', function () {
    $customer = User::factory()->create([
        'name' => 'Sylvia Beech',
        'email' => 'sylvia@greenveil.test',
    ]);

    $response = $this->actingAs($customer)->get('/account/profile');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Account/Profile')
            ->has('profile', fn (Assert $prof) => $prof
                ->where('name', 'Sylvia Beech')
                ->where('email', 'sylvia@greenveil.test')
                ->etc()
            )
        );
});

test('customers can update their profile information', function () {
    $customer = User::factory()->create([
        'name' => 'Sylvia Beech',
        'email' => 'sylvia@greenveil.test',
    ]);

    $response = $this->actingAs($customer)->patch('/account/profile', [
        'name' => 'Sylvia Beech-Wood',
        'email' => 'sylvia.wood@greenveil.test',
    ]);

    $response->assertRedirect();
    $customer->refresh();

    expect($customer->name)->toBe('Sylvia Beech-Wood');
    expect($customer->email)->toBe('sylvia.wood@greenveil.test');
});

test('email uniqueness is enforced during profile update excluding own email', function () {
    $other = User::factory()->create([
        'email' => 'other@greenveil.test',
    ]);

    $customer = User::factory()->create([
        'name' => 'Sylvia Beech',
        'email' => 'sylvia@greenveil.test',
    ]);

    // Attempt to take existing other user's email
    $response = $this->actingAs($customer)->patch('/account/profile', [
        'name' => 'Sylvia Beech',
        'email' => 'other@greenveil.test',
    ]);

    $response->assertSessionHasErrors('email');
    $customer->refresh();
    expect($customer->email)->toBe('sylvia@greenveil.test');

    // Saving own email without change should succeed
    $okResponse = $this->actingAs($customer)->patch('/account/profile', [
        'name' => 'Sylvia Beech Updated',
        'email' => 'sylvia@greenveil.test',
    ]);

    $okResponse->assertSessionHasNoErrors();
    $customer->refresh();
    expect($customer->name)->toBe('Sylvia Beech Updated');
});

test('customers can update their account password', function () {
    $customer = User::factory()->create([
        'password' => bcrypt('old-secret-pass123'),
    ]);

    $response = $this->actingAs($customer)->put('/account/password', [
        'current_password' => 'old-secret-pass123',
        'password' => 'new-secret-pass456',
        'password_confirmation' => 'new-secret-pass456',
    ]);

    $response->assertRedirect();
    $customer->refresh();

    expect(Hash::check('new-secret-pass456', $customer->password))->toBeTrue();
});

test('password update fails when current password does not match', function () {
    $customer = User::factory()->create([
        'password' => bcrypt('correct-password123'),
    ]);

    $response = $this->actingAs($customer)->put('/account/password', [
        'current_password' => 'wrong-guess-password',
        'password' => 'new-secret-pass456',
        'password_confirmation' => 'new-secret-pass456',
    ]);

    $response->assertSessionHasErrors('current_password');
    $customer->refresh();

    expect(Hash::check('correct-password123', $customer->password))->toBeTrue();
});

test('password update fails when confirmation does not match', function () {
    $customer = User::factory()->create([
        'password' => bcrypt('correct-password123'),
    ]);

    $response = $this->actingAs($customer)->put('/account/password', [
        'current_password' => 'correct-password123',
        'password' => 'new-secret-pass456',
        'password_confirmation' => 'mismatch-pass789',
    ]);

    $response->assertSessionHasErrors('password');
});
