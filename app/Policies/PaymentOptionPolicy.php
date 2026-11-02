<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\PaymentOption;
use App\Models\User;

class PaymentOptionPolicy
{
    /**
     * Determine whether the user can view any payment options.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_payment_option') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can view the specific payment option.
     */
    public function view(User|AdminUser $user, PaymentOption $paymentOption): bool
    {
        return $user->can('view_payment_option') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can create payment options.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_payment_option') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can update the payment option.
     */
    public function update(User|AdminUser $user, PaymentOption $paymentOption): bool
    {
        return $user->can('update_payment_option') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can delete the payment option.
     */
    public function delete(User|AdminUser $user, PaymentOption $paymentOption): bool
    {
        return $user->can('delete_payment_option') && $user->hasCategoryAccess('settings');
    }
}
