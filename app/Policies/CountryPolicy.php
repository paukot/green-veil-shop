<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Country;
use App\Models\User;

class CountryPolicy
{
    /**
     * Determine whether the user can view any countries.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_country') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can view the country.
     */
    public function view(User|AdminUser $user, Country $country): bool
    {
        return $user->can('view_country') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can create countries.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_country') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can update the country.
     */
    public function update(User|AdminUser $user, Country $country): bool
    {
        return $user->can('update_country') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can delete the country.
     */
    public function delete(User|AdminUser $user, Country $country): bool
    {
        return $user->can('delete_country') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can restore the country.
     */
    public function restore(User|AdminUser $user, Country $country): bool
    {
        return $user->can('restore_country') && $user->hasCategoryAccess('settings');
    }

    /**
     * Determine whether the user can permanently delete the country.
     */
    public function forceDelete(User|AdminUser $user, Country $country): bool
    {
        return $user->can('force_delete_country') && $user->hasCategoryAccess('settings');
    }
}
