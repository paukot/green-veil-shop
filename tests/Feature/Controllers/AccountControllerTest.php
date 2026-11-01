<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('AccountController', function () {
    describe('edit', function () {
        it('redirects guests to login route', function () {
            $response = $this->get(route('account.profile'));

            $response->assertRedirect(route('login'));
        });

        it('renders profile page with user details for authenticated customer', function () {
            $user = User::factory()->create([
                'name' => 'Rose Tyler',
                'email' => 'rose.tyler@example.com',
            ]);

            $response = $this->actingAs($user)->get(route('account.profile'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->where('profile.name', 'Rose Tyler')
                ->where('profile.email', 'rose.tyler@example.com')
                ->has('profile.created_at')
            );
        });
    });

    describe('updateProfile', function () {
        it('requires authentication to update profile', function () {
            $response = $this->patch(route('account.profile.update'), [
                'name' => 'Hacker Name',
                'email' => 'hacker@example.com',
            ]);

            $response->assertRedirect(route('login'));
        });

        it('validates profile update input', function () {
            $user = User::factory()->create();
            $otherUser = User::factory()->create(['email' => 'other@example.com']);

            // Empty name & email
            $responseEmpty = $this->actingAs($user)->patch(route('account.profile.update'), []);
            $responseEmpty->assertSessionHasErrors(['name', 'email']);

            // Duplicate email of other user
            $responseDuplicate = $this->actingAs($user)->patch(route('account.profile.update'), [
                'name' => 'Valid Name',
                'email' => 'other@example.com',
            ]);
            $responseDuplicate->assertSessionHasErrors(['email']);
        });

        it('updates user profile information successfully', function () {
            $user = User::factory()->create([
                'name' => 'Original Name',
                'email' => 'original@example.com',
            ]);

            $response = $this->actingAs($user)->patch(route('account.profile.update'), [
                'name' => 'Botanical Expert',
                'email' => 'expert@example.com',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect();
            $response->assertSessionHas('success');

            expect($user->fresh()->name)->toBe('Botanical Expert')
                ->and($user->fresh()->email)->toBe('expert@example.com');
        });

        it('resets email_verified_at when email address is changed', function () {
            $user = User::factory()->create([
                'email' => 'verified@example.com',
                'email_verified_at' => now(),
            ]);

            $response = $this->actingAs($user)->patch(route('account.profile.update'), [
                'name' => $user->name,
                'email' => 'new.email@example.com',
            ]);

            $response->assertSessionHasNoErrors();
            expect($user->fresh()->email_verified_at)->toBeNull();
        });

        it('allows user to keep their current email without triggering unique violation', function () {
            $user = User::factory()->create([
                'name' => 'Current User',
                'email' => 'staying.same@example.com',
            ]);

            $response = $this->actingAs($user)->patch(route('account.profile.update'), [
                'name' => 'Updated Display Name',
                'email' => 'staying.same@example.com',
            ]);

            $response->assertSessionHasNoErrors();
            expect($user->fresh()->name)->toBe('Updated Display Name');
        });
    });

    describe('updatePassword', function () {
        it('requires authentication to update password', function () {
            $response = $this->put(route('account.password.update'), [
                'current_password' => 'secret',
                'password' => 'newsecret123',
                'password_confirmation' => 'newsecret123',
            ]);

            $response->assertRedirect(route('login'));
        });

        it('validates current password, password length, and confirmation', function () {
            $user = User::factory()->create([
                'password' => Hash::make('ActualPassword123!'),
            ]);

            // Wrong current password
            $responseWrongCurrent = $this->actingAs($user)->put(route('account.password.update'), [
                'current_password' => 'WrongCurrentPassword',
                'password' => 'ValidNewPassword123!',
                'password_confirmation' => 'ValidNewPassword123!',
            ]);
            $responseWrongCurrent->assertSessionHasErrors(['current_password']);

            // Short password and mismatched confirmation
            $responseMismatched = $this->actingAs($user)->put(route('account.password.update'), [
                'current_password' => 'ActualPassword123!',
                'password' => 'short',
                'password_confirmation' => 'different',
            ]);
            $responseMismatched->assertSessionHasErrors(['password']);
        });

        it('updates user password hash on valid submission', function () {
            $user = User::factory()->create([
                'password' => Hash::make('OldPassword123!'),
            ]);

            $response = $this->actingAs($user)->put(route('account.password.update'), [
                'current_password' => 'OldPassword123!',
                'password' => 'BrandNewPassword123!',
                'password_confirmation' => 'BrandNewPassword123!',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect();
            $response->assertSessionHas('success');

            expect(Hash::check('BrandNewPassword123!', $user->fresh()->password))->toBeTrue();
        });
    });
});
