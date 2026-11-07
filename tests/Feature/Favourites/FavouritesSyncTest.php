<?php

use App\Models\Favourite;
use App\Models\Product;
use App\Models\User;

describe('Favourites Synchronization', function () {
    it('syncs favourites in guest session filtering out inactive or invalid product IDs', function () {
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

    it('syncs favourites in database for authenticated customer and prunes unlisted IDs', function () {
        $user = User::factory()->create();
        $existingProduct = Product::factory()->create(['is_active' => true]);
        $newProduct = Product::factory()->create(['is_active' => true]);
        $staleProduct = Product::factory()->create(['is_active' => true]);

        Favourite::create(['user_id' => $user->id, 'product_id' => $existingProduct->id]);
        Favourite::create(['user_id' => $user->id, 'product_id' => $staleProduct->id]);

        $response = actingAsCustomer($user)->postJson(route('favourites.sync'), [
            'product_ids' => [$existingProduct->id, $newProduct->id],
        ]);

        $response->assertOk()
            ->assertJson([
                'count' => 2,
            ]);

        expect(Favourite::where('user_id', $user->id)->pluck('product_id')->all())
            ->toEqualCanonicalizing([$existingProduct->id, $newProduct->id]);

        expect(Favourite::where('user_id', $user->id)->where('product_id', $staleProduct->id)->exists())
            ->toBeFalse();
    });

    it('clears all favourites when syncing an empty product IDs array', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);
        Favourite::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $response = actingAsCustomer($user)->postJson(route('favourites.sync'), [
            'product_ids' => [],
        ]);

        $response->assertOk()
            ->assertJson([
                'favourites' => [],
                'count' => 0,
            ]);

        expect(Favourite::where('user_id', $user->id)->count())->toBe(0);
    });

    it('validates product_ids array payload on sync', function () {
        $response = $this->postJson(route('favourites.sync'), [
            'product_ids' => 'not-an-array',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_ids']);
    });
});
