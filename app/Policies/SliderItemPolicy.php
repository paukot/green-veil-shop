<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\SliderItem;
use App\Models\User;

class SliderItemPolicy
{
    /**
     * Determine whether the user can view any hero slider items.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_slider_item') && $user->hasCategoryAccess('marketing');
    }

    /**
     * Determine whether the user can view the specific hero slider item.
     */
    public function view(User|AdminUser $user, SliderItem $sliderItem): bool
    {
        return $user->can('view_slider_item') && $user->hasCategoryAccess('marketing');
    }

    /**
     * Determine whether the user can create hero slider items.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_slider_item') && $user->hasCategoryAccess('marketing');
    }

    /**
     * Determine whether the user can update the hero slider item.
     */
    public function update(User|AdminUser $user, SliderItem $sliderItem): bool
    {
        return $user->can('update_slider_item') && $user->hasCategoryAccess('marketing');
    }

    /**
     * Determine whether the user can delete the hero slider item.
     */
    public function delete(User|AdminUser $user, SliderItem $sliderItem): bool
    {
        return $user->can('delete_slider_item') && $user->hasCategoryAccess('marketing');
    }
}
