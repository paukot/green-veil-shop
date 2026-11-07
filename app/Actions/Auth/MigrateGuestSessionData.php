<?php

namespace App\Actions\Auth;

use App\Actions\Checkout\ClaimGuestOrders;
use App\Models\User;
use App\Services\CartService;
use App\Services\FavouriteService;
use Illuminate\Http\Request;

class MigrateGuestSessionData
{
    public function __construct(
        protected CartService $cartService,
        protected FavouriteService $favouriteService,
        protected ClaimGuestOrders $claimGuestOrders,
    ) {}

    /**
     * Migrate guest shopping cart and favourites into the authenticated customer account.
     *
     * @param  array<mixed>  $guestCart
     * @param  array<mixed>  $guestFavourites
     */
    public function execute(User $user, array $guestCart, array $guestFavourites, Request $request): void
    {
        $this->cartService->mergeGuestCart($guestCart, $user);
        $this->favouriteService->mergeGuestFavourites($guestFavourites, $user);
        $this->claimGuestOrders->execute($user);

        $request->session()->forget(['cart', 'favourites']);
    }
}
