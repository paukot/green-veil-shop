<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\RelationManagers\UserAddressesRelationManager;
use App\Filament\Resources\Users\RelationManagers\UserCartRelationManager;
use App\Filament\Resources\Users\RelationManagers\UserOrdersRelationManager;
use App\Filament\Resources\Users\UserResource;
use App\Models\AdminUser;
use App\Models\CartItem;
use App\Models\CustomerAddress;
use App\Models\Favourite;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

describe('UserResource in Filament', function () {
    beforeEach(function () {
        $this->admin = AdminUser::factory()->superAdmin()->create();
    });

    it('denies access to non-admins', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->get('/admin/users')
            ->assertRedirect('/admin/login');
    });

    it('renders list page with customer accounts', function () {
        $customer = User::factory()->create([
            'name' => 'Rose Tyler',
            'email' => 'rose@example.com',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Rose Tyler')
            ->assertSee('rose@example.com');

        Livewire::actingAs($this->admin, 'admin')
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords([$customer]);
    });

    it('allows admin to create a new customer with hashed password', function () {
        Livewire::actingAs($this->admin, 'admin')
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Martha Jones',
                'email' => 'martha@example.com',
                'password' => 'secret12345',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $newUser = User::where('email', 'martha@example.com')->first();
        expect($newUser)->not->toBeNull();
        expect(Hash::check('secret12345', $newUser->password))->toBeTrue();
    });

    it('allows admin to update an existing customer account', function () {
        $customer = User::factory()->create([
            'name' => 'Original Name',
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(EditUser::class, ['record' => $customer->id])
            ->fillForm([
                'name' => 'Updated Customer Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($customer->fresh()->name)->toBe('Updated Customer Name');
    });

    it('renders customer 360 relation managers (Orders, Cart, Wishlist)', function () {
        $customer = User::factory()->create(['name' => 'Donna Noble']);
        $product = Product::factory()->create(['name' => 'Rose Hydrosol', 'price' => 24.00]);

        $order = Order::factory()->create(['user_id' => $customer->id, 'total' => 48.00]);
        $cartItem = CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 2]);
        $favourite = Favourite::create(['user_id' => $customer->id, 'product_id' => $product->id]);

        // Orders relation manager
        Livewire::actingAs($this->admin, 'admin')
            ->test(UserOrdersRelationManager::class, [
                'ownerRecord' => $customer,
                'pageClass' => EditUser::class,
            ])
            ->assertCanSeeTableRecords([$order])
            ->assertSee($order->number);

        // Cart relation manager
        Livewire::actingAs($this->admin, 'admin')
            ->test(UserCartRelationManager::class, [
                'ownerRecord' => $customer,
                'pageClass' => EditUser::class,
            ])
            ->assertCanSeeTableRecords([$cartItem])
            ->assertSee('Rose Hydrosol')
            ->assertSee('€48.00');

        // Addresses relation manager (read-only customer addresses)
        $address = CustomerAddress::factory()->create([
            'user_id' => $customer->id,
            'label' => 'Sanctuary Flat',
            'recipient_name' => 'Donna Noble',
            'line1' => '7 Chiswick High Road',
            'city' => 'London',
            'postal_code' => 'W4 2AA',
            'country' => 'GB',
        ]);

        Livewire::actingAs($this->admin, 'admin')
            ->test(UserAddressesRelationManager::class, [
                'ownerRecord' => $customer,
                'pageClass' => EditUser::class,
            ])
            ->assertCanSeeTableRecords([$address])
            ->assertSee('Sanctuary Flat')
            ->assertSee('7 Chiswick High Road')
            ->assertActionDoesNotExist('create');
    });

    it('defines globally searchable attributes on UserResource', function () {
        $attributes = UserResource::getGloballySearchableAttributes();
        expect($attributes)->toContain('name', 'email');
    });
});
