<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    /**
     * Determine whether the user can view any brands.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_brand') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can view the specific brand.
     */
    public function view(User|AdminUser $user, Brand $brand): bool
    {
        return $user->can('view_brand') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can create brands.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_brand') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can update the brand.
     */
    public function update(User|AdminUser $user, Brand $brand): bool
    {
        return $user->can('update_brand') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can delete the brand.
     */
    public function delete(User|AdminUser $user, Brand $brand): bool
    {
        return $user->can('delete_brand') && $user->hasCategoryAccess('shop');
    }
}
