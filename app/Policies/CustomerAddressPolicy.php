<?php

namespace App\Policies;

use App\Models\CustomerAddress;
use App\Models\User;

class CustomerAddressPolicy
{
    /**
     * Customers may only change their own saved addresses.
     */
    public function update(User $user, CustomerAddress $address): bool
    {
        return $address->user_id === $user->id;
    }

    public function delete(User $user, CustomerAddress $address): bool
    {
        return $this->update($user, $address);
    }
}
