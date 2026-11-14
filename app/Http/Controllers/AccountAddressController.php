<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\StoreCustomerAddressRequest;
use App\Http\Requests\Account\UpdateCustomerAddressRequest;
use App\Http\Resources\CustomerAddressResource;
use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountAddressController extends Controller
{
    /**
     * Display the customer's saved shipping addresses.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Account/Addresses', [
            'addresses' => CustomerAddressResource::collection($user->addresses)->resolve(),
            'countries' => Country::getShippingOptions(),
            'maxAddresses' => CustomerAddress::MAX_PER_USER,
            'canAddAddress' => $user->canAddAddress(),
        ]);
    }

    /**
     * Store a newly created shipping address for the customer.
     */
    public function store(StoreCustomerAddressRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->addresses()->create($request->addressData());

        return to_route('account.addresses.index')->with('success', 'Shipping address added successfully.');
    }

    /**
     * Update an existing customer shipping address.
     */
    public function update(UpdateCustomerAddressRequest $request, CustomerAddress $address): RedirectResponse
    {
        $address->update($request->addressData());

        return to_route('account.addresses.index')->with('success', 'Shipping address updated successfully.');
    }

    /**
     * Delete a customer shipping address.
     */
    public function destroy(Request $request, CustomerAddress $address): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($address->user_id !== $user->id) {
            abort(403);
        }

        $address->delete();

        return to_route('account.addresses.index')->with('success', 'Shipping address removed.');
    }

    /**
     * Mark an address as the customer's default shipping address.
     */
    public function setDefault(Request $request, CustomerAddress $address): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($address->user_id !== $user->id) {
            abort(403);
        }

        $address->update(['is_default' => true]);

        return to_route('account.addresses.index')->with('success', 'Default shipping address updated.');
    }
}
