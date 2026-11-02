<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_order') && $user->hasCategoryAccess('orders');
    }

    /**
     * Determine whether the user can view the specific order.
     */
    public function view(User|AdminUser $user, Order $order): bool
    {
        if ($user instanceof User) {
            return (int) $order->user_id === $user->id;
        }

        return $user->can('view_order') && $user->hasCategoryAccess('orders');
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User|AdminUser $user): bool
    {
        return $user->can('create_order') && $user->hasCategoryAccess('orders');
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User|AdminUser $user, Order $order): bool
    {
        return $user->can('update_order') && $user->hasCategoryAccess('orders');
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete(User|AdminUser $user, Order $order): bool
    {
        return $user->can('delete_order') && $user->hasCategoryAccess('orders');
    }
}
