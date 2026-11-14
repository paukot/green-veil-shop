<?php

use App\Actions\Checkout\CheckoutData;
use App\Actions\Checkout\PlaceOrder;
use App\Models\CartItem;
use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\DeliveryOption;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;

describe('Customer Shipping Addresses', function () {
    describe('authentication & access', function () {
        it('redirects guests to login when viewing addresses', function () {
            $response = $this->get(route('account.addresses.index'));
            $response->assertRedirect(route('login'));
        });

        it('redirects guests attempting to store an address', function () {
            $response = $this->post(route('account.addresses.store'), [
                'recipient_name' => 'Guest User',
                'line1' => '123 Forest Lane',
                'city' => 'Dublin',
                'postal_code' => 'D01 1AA',
                'country' => 'IE',
            ]);
            $response->assertRedirect(route('login'));
        });
    });

    describe('view addresses', function () {
        it('renders addresses list with customer addresses and countries list', function () {
            Country::firstOrCreate(
                ['code' => 'IE'],
                [
                    'name' => 'Ireland',
                    'is_active' => true,
                    'is_shipping_available' => true,
                    'sort_order' => 1,
                ]
            );

            $user = User::factory()->create();
            CustomerAddress::factory()->create([
                'user_id' => $user->id,
                'label' => 'Home Sanctuary',
                'recipient_name' => 'Aoife Green',
                'phone' => '+353 87 123 4567',
                'line1' => '12 Grafton Street',
                'city' => 'Dublin',
                'postal_code' => 'D02 EF12',
                'country' => 'IE',
                'is_default' => true,
            ]);

            $response = actingAsCustomer($user)->get(route('account.addresses.index'));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Account/Addresses')
                ->has('addresses', 1)
                ->where('addresses.0.label', 'Home Sanctuary')
                ->where('addresses.0.recipient_name', 'Aoife Green')
                ->where('addresses.0.phone', '+353 87 123 4567')
                ->where('addresses.0.is_default', true)
                ->where('canAddAddress', true)
                ->has('countries')
            );
        });

        it('isolates addresses by user', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            CustomerAddress::factory()->create([
                'user_id' => $userA->id,
                'label' => 'User A Home',
            ]);
            CustomerAddress::factory()->create([
                'user_id' => $userB->id,
                'label' => 'User B Home',
            ]);

            $response = actingAsCustomer($userA)->get(route('account.addresses.index'));

            $response->assertInertia(fn (Assert $page) => $page
                ->component('Account/Addresses')
                ->has('addresses', 1)
                ->where('addresses.0.label', 'User A Home')
            );
        });
    });

    describe('create address', function () {
        it('creates a new address and automatically sets the first address as default', function () {
            Country::firstOrCreate(
                ['code' => 'GB'],
                [
                    'name' => 'United Kingdom',
                    'is_active' => true,
                    'is_shipping_available' => true,
                    'sort_order' => 2,
                ]
            );

            $user = User::factory()->create();

            $payload = [
                'label' => 'Studio',
                'recipient_name' => 'Jane Doe',
                'phone' => '+44 20 7946 0991',
                'line1' => '221B Baker Street',
                'line2' => 'Flat B',
                'city' => 'London',
                'postal_code' => 'NW1 6XE',
                'country' => 'GB',
                'is_default' => false, // should become default because it is the first address
            ];

            $response = actingAsCustomer($user)->post(route('account.addresses.store'), $payload);

            $response->assertRedirect(route('account.addresses.index'));
            $response->assertSessionHas('success');

            $this->assertDatabaseHas('customer_addresses', [
                'user_id' => $user->id,
                'label' => 'Studio',
                'recipient_name' => 'Jane Doe',
                'phone' => '+44 20 7946 0991',
                'is_default' => true,
            ]);
        });

        it('switches default address when adding a second address with is_default true', function () {
            Country::firstOrCreate(
                ['code' => 'IE'],
                [
                    'name' => 'Ireland',
                    'is_active' => true,
                    'is_shipping_available' => true,
                    'sort_order' => 1,
                ]
            );

            $user = User::factory()->create();

            $firstAddress = CustomerAddress::factory()->create([
                'user_id' => $user->id,
                'label' => 'First Address',
                'country' => 'IE',
                'is_default' => true,
            ]);

            $response = actingAsCustomer($user)->post(route('account.addresses.store'), [
                'label' => 'Second Address',
                'recipient_name' => 'Jane Doe',
                'phone' => '+353 1 234 5678',
                'line1' => '10 O\'Connell Street',
                'city' => 'Dublin',
                'postal_code' => 'D01 1234',
                'country' => 'IE',
                'is_default' => true,
            ]);

            $response->assertRedirect(route('account.addresses.index'));

            expect($firstAddress->fresh()->is_default)->toBeFalse();
            $secondAddress = $user->addresses()->where('label', 'Second Address')->first();
            expect($secondAddress)->not->toBeNull();
            expect($secondAddress->is_default)->toBeTrue();
        });

        it('strictly enforces maximum limit of 3 addresses per customer', function () {
            Country::firstOrCreate(
                ['code' => 'IE'],
                [
                    'name' => 'Ireland',
                    'is_active' => true,
                    'is_shipping_available' => true,
                    'sort_order' => 1,
                ]
            );

            $user = User::factory()->create();

            CustomerAddress::factory()->count(3)->create([
                'user_id' => $user->id,
                'country' => 'IE',
            ]);

            expect($user->addresses()->count())->toBe(3);
            expect($user->canAddAddress())->toBeFalse();

            $response = actingAsCustomer($user)->post(route('account.addresses.store'), [
                'label' => 'Fourth Address',
                'recipient_name' => 'Jane Doe',
                'phone' => '+353 1 234 5678',
                'line1' => 'Over The Limit Way',
                'city' => 'Dublin',
                'postal_code' => 'D01 1234',
                'country' => 'IE',
            ]);

            $response->assertSessionHasErrors(['address_limit']);
            expect($user->addresses()->count())->toBe(3);
        });

        it('validates required fields', function () {
            $user = User::factory()->create();

            $response = actingAsCustomer($user)->post(route('account.addresses.store'), []);

            $response->assertSessionHasErrors([
                'recipient_name',
                'line1',
                'city',
                'postal_code',
                'country',
            ]);
        });
    });

    describe('update address', function () {
        it('updates an existing address successfully', function () {
            Country::firstOrCreate(
                ['code' => 'IE'],
                [
                    'name' => 'Ireland',
                    'is_active' => true,
                    'is_shipping_available' => true,
                    'sort_order' => 1,
                ]
            );

            $user = User::factory()->create();
            $address = CustomerAddress::factory()->create([
                'user_id' => $user->id,
                'label' => 'Old Label',
                'recipient_name' => 'Old Name',
                'phone' => '+353 1 111 1111',
                'country' => 'IE',
            ]);

            $response = actingAsCustomer($user)->put(route('account.addresses.update', $address), [
                'label' => 'Renovated Villa',
                'recipient_name' => 'New Name',
                'phone' => '+353 1 999 9999',
                'line1' => $address->line1,
                'city' => $address->city,
                'postal_code' => $address->postal_code,
                'country' => $address->country,
                'is_default' => $address->is_default,
            ]);

            $response->assertRedirect(route('account.addresses.index'));
            $response->assertSessionHas('success');

            expect($address->fresh()->label)->toBe('Renovated Villa');
            expect($address->fresh()->recipient_name)->toBe('New Name');
            expect($address->fresh()->phone)->toBe('+353 1 999 9999');
        });

        it('forbids updating another user\'s address', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();
            $addressB = CustomerAddress::factory()->create([
                'user_id' => $userB->id,
            ]);

            $response = actingAsCustomer($userA)->put(route('account.addresses.update', $addressB), [
                'label' => 'Hijacked Address',
                'recipient_name' => 'Intruder',
                'line1' => 'Somewhere else',
                'city' => 'Anytown',
                'postal_code' => '00000',
                'country' => 'IE',
            ]);

            $response->assertForbidden();
        });
    });

    describe('set default & delete address', function () {
        it('allows user to set an address as default', function () {
            $user = User::factory()->create();
            $address1 = CustomerAddress::factory()->create([
                'user_id' => $user->id,
                'is_default' => true,
            ]);
            $address2 = CustomerAddress::factory()->create([
                'user_id' => $user->id,
                'is_default' => false,
            ]);

            $response = actingAsCustomer($user)->patch(route('account.addresses.default', $address2));

            $response->assertRedirect(route('account.addresses.index'));
            expect($address1->fresh()->is_default)->toBeFalse();
            expect($address2->fresh()->is_default)->toBeTrue();
        });

        it('forbids setting default on another user\'s address', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();
            $addressB = CustomerAddress::factory()->create([
                'user_id' => $userB->id,
            ]);

            $response = actingAsCustomer($userA)->patch(route('account.addresses.default', $addressB));
            $response->assertForbidden();
        });

        it('deletes an address and promotes remaining address to default if default was deleted', function () {
            $user = User::factory()->create();
            $defaultAddress = CustomerAddress::factory()->create([
                'user_id' => $user->id,
                'is_default' => true,
            ]);
            $secondaryAddress = CustomerAddress::factory()->create([
                'user_id' => $user->id,
                'is_default' => false,
            ]);

            $response = actingAsCustomer($user)->delete(route('account.addresses.destroy', $defaultAddress));

            $response->assertRedirect(route('account.addresses.index'));
            $this->assertDatabaseMissing('customer_addresses', ['id' => $defaultAddress->id]);

            expect($secondaryAddress->fresh()->is_default)->toBeTrue();
        });

        it('forbids deleting another user\'s address', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();
            $addressB = CustomerAddress::factory()->create([
                'user_id' => $userB->id,
            ]);

            $response = actingAsCustomer($userA)->delete(route('account.addresses.destroy', $addressB));
            $response->assertForbidden();
            $this->assertDatabaseHas('customer_addresses', ['id' => $addressB->id]);
        });
    });

    describe('checkout integration', function () {
        it('saves address to customer profile during checkout when save_address_to_profile is true', function () {
            $user = User::factory()->create();
            $product = Product::factory()->create([
                'price' => 50.00,
                'stock_quantity' => 10,
                'is_active' => true,
            ]);

            CartItem::factory()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

            $delivery = DeliveryOption::factory()->create(['price' => 5.00]);
            $payment = PaymentOption::factory()->cod()->create();

            $checkoutData = new CheckoutData(
                customerName: 'Margot Botanical',
                customerEmail: $user->email,
                customerPhone: '+353 87 111 2233',
                deliveryOptionId: $delivery->id,
                paymentOptionId: $payment->id,
                shippingAddress: [
                    'line1' => '88 Rosehip Lane',
                    'city' => 'Galway',
                    'postal_code' => 'H91 1234',
                    'country' => 'IE',
                ],
                billingAddress: [
                    'line1' => '88 Rosehip Lane',
                    'city' => 'Galway',
                    'postal_code' => 'H91 1234',
                    'country' => 'IE',
                ],
                notes: null,
                user: $user,
                promotionId: null,
                saveAddressToProfile: true,
                addressLabel: 'Galway Cottage',
            );

            $request = Request::create('/checkout/place-order', 'POST');
            $request->setUserResolver(fn () => $user);

            $placeOrder = app(PlaceOrder::class);
            $order = $placeOrder->execute($request, $checkoutData);

            expect($order)->not->toBeNull();
            $this->assertDatabaseHas('customer_addresses', [
                'user_id' => $user->id,
                'label' => 'Galway Cottage',
                'recipient_name' => 'Margot Botanical',
                'phone' => '+353 87 111 2233',
                'line1' => '88 Rosehip Lane',
                'city' => 'Galway',
                'postal_code' => 'H91 1234',
                'country' => 'IE',
            ]);
        });
    });

    describe('model helpers & normalization', function () {
        it('normalizes lowercase country codes to uppercase upon storing', function () {
            Country::firstOrCreate(
                ['code' => 'IE'],
                [
                    'name' => 'Ireland',
                    'is_active' => true,
                    'is_shipping_available' => true,
                    'sort_order' => 1,
                ]
            );

            $user = User::factory()->create();

            $response = actingAsCustomer($user)->post(route('account.addresses.store'), [
                'label' => 'Cottage',
                'recipient_name' => 'Mary Byrne',
                'line1' => '42 Green Way',
                'city' => 'Cork',
                'postal_code' => 'T12 3456',
                'country' => 'ie',
            ]);

            $response->assertRedirect(route('account.addresses.index'));
            $this->assertDatabaseHas('customer_addresses', [
                'user_id' => $user->id,
                'country' => 'IE',
            ]);
        });

        it('properly resolves default address and address limits when relation is eager loaded', function () {
            $user = User::factory()->create();
            CustomerAddress::factory()->create([
                'user_id' => $user->id,
                'label' => 'First Address',
                'is_default' => true,
            ]);
            CustomerAddress::factory()->create([
                'user_id' => $user->id,
                'label' => 'Second Address',
                'is_default' => false,
            ]);

            $user->load('addresses');

            expect($user->relationLoaded('addresses'))->toBeTrue()
                ->and($user->defaultAddress()?->label)->toBe('First Address')
                ->and($user->canAddAddress())->toBeTrue();
        });
    });
});
