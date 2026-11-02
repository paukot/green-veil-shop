<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_category') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can view the specific category.
     */
    public function view(User|AdminUser $user, Category $category): bool
    {
        return $user->can('view_category') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_category') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User|AdminUser $user, Category $category): bool
    {
        return $user->can('update_category') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User|AdminUser $user, Category $category): bool
    {
        return $user->can('delete_category') && $user->hasCategoryAccess('shop');
    }
}
