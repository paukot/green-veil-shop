<?php

use App\Models\Favourite;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('Favourites Toggle & Listing', function () {
    describe('index', function () {
        it('renders empty favourites page when guest has no favourites', function () {
            $response = $this->get(route('favourites.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Favourites/Index')
                ->has('products', 0)
            );
        });

        it('renders guest favourites from session', function () {
            $product = Product::factory()->create(['name' => 'Botanical Essence', 'is_active' => true]);

            $response = $this->withSession(['favourites' => [$product->id]])->get(route('favourites.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Favourites/Index')
                ->has('products', 1)
                ->where('products.0.id', $product->id)
                ->where('products.0.name', 'Botanical Essence')
            );
        });

        it('renders authenticated user favourites from database', function () {
            $user = User::factory()->create();
            $product = Product::factory()->create(['name' => 'Rosehip Facial Oil', 'is_active' => true]);
            Favourite::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);

            $response = actingAsCustomer($user)->get(route('favourites.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Favourites/Index')
                ->has('products', 1)
                ->where('products.0.id', $product->id)
                ->where('products.0.name', 'Rosehip Facial Oil')
            );
        });

        it('redirects guests attempting to access account rituals to login', function () {
            $response = $this->get(route('account.rituals'));

            $response->assertRedirect(route('login'));
        });
    });

    describe('toggle', function () {
        it('toggles favourite on and off in guest session', function () {
            $product = Product::factory()->create(['is_active' => true]);

            // Add to session favourites
            $responseAdd = $this->post(route('favourites.toggle', $product));

            $responseAdd->assertSessionHasNoErrors();
            $responseAdd->assertRedirect();
            $responseAdd->assertSessionHas('favourites', [$product->id]);

            $currentFavourites = session('favourites');

            // Remove from session favourites on second toggle
            $responseRemove = $this->withSession(['favourites' => $currentFavourites])
                ->post(route('favourites.toggle', $product));

            $responseRemove->assertSessionHasNoErrors();
            $responseRemove->assertSessionHas('favourites', []);
        });

        it('toggles favourite on and off in database for authenticated customer', function () {
            $user = User::factory()->create();
            $product = Product::factory()->create(['is_active' => true]);

            // Add
            $responseAdd = actingAsCustomer($user)->post(route('favourites.toggle', $product));

            $responseAdd->assertSessionHasNoErrors();
            $this->assertDatabaseHas('favourites', [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);

            // Remove
            $responseRemove = actingAsCustomer($user)->post(route('favourites.toggle', $product));

            $responseRemove->assertSessionHasNoErrors();
            $this->assertDatabaseMissing('favourites', [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        });

        it('cannot favourite an inactive product', function () {
            $inactive = Product::factory()->create(['is_active' => false]);

            $response = $this->post(route('favourites.toggle', $inactive));

            $response->assertNotFound();
        });
    });
});
