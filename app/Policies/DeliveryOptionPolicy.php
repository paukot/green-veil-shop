<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\DeliveryOption;
use App\Models\User;

class DeliveryOptionPolicy
{
    /**
     * Determine whether the user can view any delivery options.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_delivery_option') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can view the specific delivery option.
     */
    public function view(User|AdminUser $user, DeliveryOption $deliveryOption): bool
    {
        return $user->can('view_delivery_option') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can create delivery options.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_delivery_option') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can update the delivery option.
     */
    public function update(User|AdminUser $user, DeliveryOption $deliveryOption): bool
    {
        return $user->can('update_delivery_option') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can delete the delivery option.
     */
    public function delete(User|AdminUser $user, DeliveryOption $deliveryOption): bool
    {
        return $user->can('delete_delivery_option') && $user->hasCategoryAccess('settings');
    }
}
