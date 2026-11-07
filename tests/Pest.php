<?php

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        config()->set('inertia.ssr.enabled', false);
        Http::preventStrayRequests();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| Global helpers to reduce boilerplate across feature and unit tests.
|
*/

function actingAsCustomer(?User $user = null): TestCase
{
    $user ??= User::factory()->create();

    return test()->actingAs($user, 'web');
}

function actingAsAdmin(?AdminUser $admin = null): TestCase
{
    $admin ??= AdminUser::factory()->superAdmin()->create();

    return test()->actingAs($admin, 'admin');
}
