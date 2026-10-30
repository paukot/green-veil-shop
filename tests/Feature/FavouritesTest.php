<?php

use App\Models\Category;
use App\Models\Favourite;
use App\Models\Product;
use App\Models\User;
use App\Services\FavouriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guest can toggle favourite on and off in session', function () {
    $product = Product::factory()->create(['is_active' => true]);

    // First toggle: adds to favourites
    $responseAdd = $this->post(route('favourites.toggle', $product));

    $responseAdd->assertSessionHasNoErrors();
    $responseAdd->assertRedirect();
    $responseAdd->assertSessionHas('favourites', [$product->id]);

    $currentFavourites = session('favourites');

    // Second toggle with same session: removes from favourites
    $responseRemove = $this->withSession(['favourites' => $currentFavourites])
        ->post(route('favourites.toggle', $product));

    $responseRemove->assertSessionHasNoErrors();
    $responseRemove->assertSessionHas('favourites', []);
});

test('authenticated user can toggle favourite on and off in database', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['is_active' => true]);

    // First toggle: persists in database
    $responseAdd = $this->actingAs($user)->post(route('favourites.toggle', $product));

    $responseAdd->assertSessionHasNoErrors();
    $this->assertDatabaseHas('favourites', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    // Second toggle: deletes from database
    $responseRemove = $this->actingAs($user)->post(route('favourites.toggle', $product));

    $responseRemove->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('favourites', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

test('cannot favourite an inactive product', function () {
    $inactive = Product::factory()->create(['is_active' => false]);

    $response = $this->post(route('favourites.toggle', $inactive));

    $response->assertNotFound();
});

test('favourites page renders with empty state when no items saved', function () {
    $response = $this->get(route('favourites.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Favourites/Index')
        ->has('products', 0)
    );
});

test('favourites page displays saved active products', function () {
    $category = Category::factory()->create(['name' => 'Elixirs']);
    $user = User::factory()->create();

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Night Repair Oil',
        'is_active' => true,
    ]);

    Favourite::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    $response = $this->actingAs($user)->get(route('favourites.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Favourites/Index')
        ->has('products', 1)
        ->where('products.0.name', 'Night Repair Oil')
    );
});

test('guest favourites merge into user account', function () {
    $user = User::factory()->create();
    $product1 = Product::factory()->create(['is_active' => true]);
    $product2 = Product::factory()->create(['is_active' => true]);

    // User already has product 1
    Favourite::create([
        'user_id' => $user->id,
        'product_id' => $product1->id,
    ]);

    $guestFavourites = [$product1->id, $product2->id];

    $favouriteService = app(FavouriteService::class);
    $favouriteService->mergeGuestFavourites($guestFavourites, $user);

    $this->assertDatabaseHas('favourites', [
        'user_id' => $user->id,
        'product_id' => $product1->id,
    ]);

    $this->assertDatabaseHas('favourites', [
        'user_id' => $user->id,
        'product_id' => $product2->id,
    ]);

    $this->assertEquals(2, Favourite::where('user_id', $user->id)->count());
});
