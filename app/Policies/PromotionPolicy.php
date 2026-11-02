<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    /**
     * Determine whether the user can view any promotions.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_promotion') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can view the specific promotion.
     */
    public function view(User|AdminUser $user, Promotion $promotion): bool
    {
        return $user->can('view_promotion') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can create promotions.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_promotion') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can update the promotion.
     */
    public function update(User|AdminUser $user, Promotion $promotion): bool
    {
        return $user->can('update_promotion') && $user->hasCategoryAccess('shop');
    }

    /**
     * Determine whether the user can delete the promotion.
     */
    public function delete(User|AdminUser $user, Promotion $promotion): bool
    {
        return $user->can('delete_promotion') && $user->hasCategoryAccess('shop');
    }
}
