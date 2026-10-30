<?php

namespace App\Services;

use App\Models\Favourite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class FavouriteService
{
    /**
     * Get list of favourited product IDs for the current user or guest session.
     *
     * @return array<int>
     */
    public function getFavouriteProductIds(Request $request): array
    {
        if ($user = $request->user()) {
            return Favourite::query()
                ->where('user_id', $user->id)
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $sessionFavourites = $request->session()->get('favourites', []);

        if (! is_array($sessionFavourites)) {
            return [];
        }

        return array_values(array_map('intval', $sessionFavourites));
    }

    /**
     * Get active Product models favourited by the user or guest session.
     *
     * @return Collection<int, Product>
     */
    public function getFavouriteProducts(Request $request): Collection
    {
        $ids = $this->getFavouriteProductIds($request);

        if (empty($ids)) {
            return new Collection;
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->with(['category:id,slug,name'])
            ->get();
    }

    /**
     * Toggle a product in favourites. Returns true if added, false if removed.
     */
    public function toggle(Request $request, Product $product): bool
    {
        if ($user = $request->user()) {
            $existing = Favourite::query()
                ->where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existing) {
                $existing->delete();

                return false;
            }

            Favourite::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);

            return true;
        }

        // Guest session
        $favourites = $this->getFavouriteProductIds($request);

        if (in_array($product->id, $favourites, true)) {
            $updated = array_values(array_diff($favourites, [$product->id]));
            $request->session()->put('favourites', $updated);

            return false;
        }

        $favourites[] = $product->id;
        $request->session()->put('favourites', array_values(array_unique($favourites)));

        return true;
    }

    /**
     * Merge guest session favourites into the user account upon login/registration.
     */
    public function mergeGuestFavourites(Request|array $requestOrFavourites, User $user): void
    {
        $guestFavourites = is_array($requestOrFavourites)
            ? $requestOrFavourites
            : ($requestOrFavourites->hasSession() ? $requestOrFavourites->session()->get('favourites', []) : []);

        if (! is_array($guestFavourites) || empty($guestFavourites)) {
            return;
        }

        foreach ($guestFavourites as $productId) {
            $pId = (int) $productId;
            if ($pId <= 0) {
                continue;
            }

            if (! Product::query()->where('id', $pId)->where('is_active', true)->exists()) {
                continue;
            }

            Favourite::firstOrCreate([
                'user_id' => $user->id,
                'product_id' => $pId,
            ]);
        }

        if ($requestOrFavourites instanceof Request && $requestOrFavourites->hasSession()) {
            $requestOrFavourites->session()->forget('favourites');
        }
    }
}
