<?php

use App\Models\Favourite;
use App\Models\Product;
use App\Models\User;

test('guest can sync favourites via session', function () {
    $activeProduct1 = Product::factory()->create(['is_active' => true]);
    $activeProduct2 = Product::factory()->create(['is_active' => true]);
    $inactiveProduct = Product::factory()->create(['is_active' => false]);

    $response = $this->postJson(route('favourites.sync'), [
        'product_ids' => [$activeProduct1->id, $activeProduct2->id, $inactiveProduct->id, 999999],
    ]);

    $response->assertOk()
        ->assertJson([
            'favourites' => [$activeProduct1->id, $activeProduct2->id],
            'count' => 2,
        ]);

    expect(session('favourites'))->toEqualCanonicalizing([$activeProduct1->id, $activeProduct2->id]);
});

test('authenticated user syncs favourites in database and prunes unlisted', function () {
    $user = User::factory()->create();
    $existingProduct = Product::factory()->create(['is_active' => true]);
    $newProduct = Product::factory()->create(['is_active' => true]);
    $staleProduct = Product::factory()->create(['is_active' => true]);

    // Seed existing favourites
    Favourite::create(['user_id' => $user->id, 'product_id' => $existingProduct->id]);
    Favourite::create(['user_id' => $user->id, 'product_id' => $staleProduct->id]);

    $response = $this->actingAs($user)->postJson(route('favourites.sync'), [
        'product_ids' => [$existingProduct->id, $newProduct->id],
    ]);

    $response->assertOk()
        ->assertJson([
            'count' => 2,
        ]);

    expect(Favourite::query()->where('user_id', $user->id)->pluck('product_id')->all())
        ->toEqualCanonicalizing([$existingProduct->id, $newProduct->id]);

    // Stale product should have been removed
    expect(Favourite::query()->where('user_id', $user->id)->where('product_id', $staleProduct->id)->exists())
        ->toBeFalse();
});

test('syncing empty favourites array clears all favourites', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['is_active' => true]);
    Favourite::create(['user_id' => $user->id, 'product_id' => $product->id]);

    $response = $this->actingAs($user)->postJson(route('favourites.sync'), [
        'product_ids' => [],
    ]);

    $response->assertOk()
        ->assertJson([
            'favourites' => [],
            'count' => 0,
        ]);

    expect(Favourite::query()->where('user_id', $user->id)->count())->toBe(0);
});

test('sync endpoint validates input payload', function () {
    $response = $this->postJson(route('favourites.sync'), [
        'product_ids' => 'not-an-array',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['product_ids']);
});
