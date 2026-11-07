<?php

use App\Models\Favourite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

describe('FavouriteController', function () {
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
            $product = Product::factory()->create(['name' => 'Botanical Essence']);

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
            $product = Product::factory()->create(['name' => 'Rosehip Facial Oil']);
            Favourite::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);

            $response = $this->actingAs($user)->get(route('favourites.index'));

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

        it('renders account rituals page for authenticated customer', function () {
            $user = User::factory()->create();
            $product = Product::factory()->create(['name' => 'Rosehip Facial Oil']);
            Favourite::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);

            $response = $this->actingAs($user)->get(route('account.rituals'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Favourites/Index')
                ->has('products', 1)
                ->where('products.0.id', $product->id)
                ->where('products.0.name', 'Rosehip Facial Oil')
            );
        });

        it('excludes inactive products from favourites list', function () {
            $user = User::factory()->create();
            $activeProduct = Product::factory()->create(['name' => 'Active Cream']);
            $inactiveProduct = Product::factory()->inactive()->create(['name' => 'Discontinued Cream']);

            Favourite::create([
                'user_id' => $user->id,
                'product_id' => $activeProduct->id,
            ]);
            Favourite::create([
                'user_id' => $user->id,
                'product_id' => $inactiveProduct->id,
            ]);

            $response = $this->actingAs($user)->get(route('favourites.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Favourites/Index')
                ->has('products', 1)
                ->where('products.0.name', 'Active Cream')
            );
        });
    });

    describe('toggle', function () {
        it('aborts with 404 when toggling inactive product', function () {
            $product = Product::factory()->inactive()->create();

            $response = $this->post(route('favourites.toggle', $product->id));

            $response->assertNotFound();
        });

        it('adds and removes product in guest session favourites', function () {
            $product = Product::factory()->create();

            // First toggle: adds to session
            $responseAdd = $this->post(route('favourites.toggle', $product->id));
            $responseAdd->assertRedirect();
            $responseAdd->assertSessionHas('success');
            $responseAdd->assertSessionHas('favourites', [$product->id]);

            $currentFavourites = session('favourites');

            // Second toggle: removes from session
            $responseRemove = $this->withSession(['favourites' => $currentFavourites])
                ->post(route('favourites.toggle', $product->id));
            $responseRemove->assertRedirect();
            $responseRemove->assertSessionHas('success');
            $responseRemove->assertSessionHas('favourites', []);
        });

        it('attaches and detaches product in database for authenticated user', function () {
            $user = User::factory()->create();
            $product = Product::factory()->create();

            // First toggle: attaches to favourites
            $responseAdd = $this->actingAs($user)->post(route('favourites.toggle', $product->id));
            $responseAdd->assertRedirect();
            $this->assertDatabaseHas('favourites', [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);

            // Second toggle: detaches from favourites
            $responseRemove = $this->actingAs($user)->post(route('favourites.toggle', $product->id));
            $responseRemove->assertRedirect();
            $this->assertDatabaseMissing('favourites', [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        });
    });
});
