<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_user') && $user->hasCategoryAccess('customers');
    }

    /**
     * Determine whether the user can view the specific customer.
     */
    public function view(User|AdminUser $user, User $model): bool
    {
        if ($user instanceof User) {
            return $user->id === $model->id;
        }

        return $user->can('view_user') && $user->hasCategoryAccess('customers');
    }

    /**
     * Determine whether the user can create customer accounts.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_user') && $user->hasCategoryAccess('customers');
    }

    /**
     * Determine whether the user can update the customer account.
     */
    public function update(User|AdminUser $user, User $model): bool
    {
        if ($user instanceof User) {
            return $user->id === $model->id;
        }

        return $user->can('update_user') && $user->hasCategoryAccess('customers');
    }

    /**
     * Determine whether the user can delete the customer account.
     */
    public function delete(User|AdminUser $user, User $model): bool
    {
        return $user->can('delete_user') && $user->hasCategoryAccess('customers');
    }
}
