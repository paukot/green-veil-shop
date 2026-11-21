<?php

namespace App\Actions\Checkout;

use App\Models\Order;
use App\Models\User;

class ClaimGuestOrders
{
    /**
     * Associate unassigned guest orders with the user by matching customer_email.
     *
     * Only a verified address proves the customer owns that mailbox; without the check,
     * registering with someone else's email would expose their order history and addresses.
     */
    public function execute(User $user): int
    {
        if (empty($user->email) || ! $user->hasVerifiedEmail()) {
            return 0;
        }

        return Order::query()
            ->whereNull('user_id')
            ->where('customer_email', $user->email)
            ->update(['user_id' => $user->id]);
    }
}
