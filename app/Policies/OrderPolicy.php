<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any orders in their account.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the specific order.
     */
    public function view(User $user, Order $order): bool
    {
        return (int) $order->user_id === $user->id;
    }
}
