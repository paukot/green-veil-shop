<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\StripeEvent;
use App\Models\User;

class StripeEventPolicy
{
    /**
     * Determine whether the user can view any stripe events.
     */
    public function viewAny(User|AdminUser $user): bool
    {
        return $user->can('view_any_stripe_event') && $user->hasCategoryAccess('system');
    }

    /**
     * Determine whether the user can view the specific stripe event.
     */
    public function view(User|AdminUser $user, StripeEvent $stripeEvent): bool
    {
        return $user->can('view_stripe_event') && $user->hasCategoryAccess('system');
    }

    /**
     * Stripe events are immutable webhook logs recorded by the webhook handler.
     */
    public function create(User|AdminUser $user): bool
    {
        return false;
    }

    public function update(User|AdminUser $user, StripeEvent $stripeEvent): bool
    {
        return false;
    }

    public function delete(User|AdminUser $user, StripeEvent $stripeEvent): bool
    {
        return false;
    }
}
