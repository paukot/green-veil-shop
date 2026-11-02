<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\RelationManagers\UserCartRelationManager;
use App\Filament\Resources\Users\RelationManagers\UserFavouritesRelationManager;
use App\Filament\Resources\Users\RelationManagers\UserOrdersRelationManager;
use App\Models\CartItem;
use App\Models\Favourite;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('UserResource in Filament', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
    });

    it('denies access to non-admins', function () {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    });

    it('renders list page with customer accounts', function () {
        $customer = User::factory()->create([
            'name' => 'Rose Tyler',
            'email' => 'rose@example.com',
            'is_admin' => false,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Rose Tyler')
            ->assertSee('rose@example.com');

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords([$customer]);
    });

    it('allows admin to create a new user with hashed password', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Martha Jones',
                'email' => 'martha@example.com',
                'password' => 'secret12345',
                'is_admin' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $newUser = User::where('email', 'martha@example.com')->first();
        expect($newUser)->not->toBeNull();
        expect(Hash::check('secret12345', $newUser->password))->toBeTrue();
        expect($newUser->is_admin)->toBeFalse();
    });

    it('allows admin to toggle administrator role on an existing user', function () {
        $customer = User::factory()->create([
            'is_admin' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $customer->id])
            ->fillForm([
                'is_admin' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($customer->fresh()->is_admin)->toBeTrue();
    });

    it('renders customer 360 relation managers (Orders, Cart, Wishlist)', function () {
        $customer = User::factory()->create(['name' => 'Donna Noble']);
        $product = Product::factory()->create(['name' => 'Rose Hydrosol', 'price' => 24.00]);

        $order = Order::factory()->create(['user_id' => $customer->id, 'total' => 48.00]);
        $cartItem = CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 2]);
        $favourite = Favourite::create(['user_id' => $customer->id, 'product_id' => $product->id]);

        // Orders relation manager
        Livewire::actingAs($this->admin)
            ->test(UserOrdersRelationManager::class, [
                'ownerRecord' => $customer,
                'pageClass' => EditUser::class,
            ])
            ->assertCanSeeTableRecords([$order])
            ->assertSee($order->number);

        // Cart relation manager
        Livewire::actingAs($this->admin)
            ->test(UserCartRelationManager::class, [
                'ownerRecord' => $customer,
                'pageClass' => EditUser::class,
            ])
            ->assertCanSeeTableRecords([$cartItem])
            ->assertSee('Rose Hydrosol')
            ->assertSee('€48.00');

        // Favourites relation manager
        Livewire::actingAs($this->admin)
            ->test(UserFavouritesRelationManager::class, [
                'ownerRecord' => $customer,
                'pageClass' => EditUser::class,
            ])
            ->assertCanSeeTableRecords([$favourite])
            ->assertSee('Rose Hydrosol');
    });
});
