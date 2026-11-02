<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any transactions.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_payment') && $user->hasCategoryAccess('finance');
    }

    /**
     * Determine whether the user can view the specific transaction.
     */
    public function view(User|AdminUser $user, Payment $payment): bool
    {
        return $user->can('view_payment') && $user->hasCategoryAccess('finance');
    }

    /**
     * Payments are recorded automatically by the payment gateway, not manually.
     */
    public function create(User|AdminUser $user): bool
    {
        return false;
    }

    public function update(User|AdminUser $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User|AdminUser $user, Payment $payment): bool
    {
        return false;
    }
}
