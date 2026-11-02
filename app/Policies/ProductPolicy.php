<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_product') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can view the specific product.
     */
    public function view(User|AdminUser $user, Product $product): bool
    {
        return $user->can('view_product') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_product') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User|AdminUser $user, Product $product): bool
    {
        return $user->can('update_product') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User|AdminUser $user, Product $product): bool
    {
        return $user->can('delete_product') && $user->hasCategoryAccess('shop');
    }
}
