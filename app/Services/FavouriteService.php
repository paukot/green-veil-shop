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
     *
     * @param  Request|array<int, int>  $requestOrFavourites
     */
    public function mergeGuestFavourites(Request|array $requestOrFavourites, User $user): void
    {
        $guestFavourites = $this->guestFavourites($requestOrFavourites);

        if (empty($guestFavourites)) {
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

    /**
     * @param  Request|array<int, int>  $source
     * @return array<int, int>
     */
    private function guestFavourites(Request|array $source): array
    {
        if (is_array($source)) {
            return $source;
        }

        if ($source->hasSession()) {
            return $source->session()->get('favourites', []);
        }

        return [];
    }

    /**
     * Reconcile favourites from frontend local storage with database/session state.
     * Prunes inactive or non-existent products.
     *
     * @param  array<int, int>  $productIds
     * @return array{favourites: array<int, int>, count: int}
     */
    public function syncFavourites(Request $request, array $productIds): array
    {
        $sanitizedIds = collect($productIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $validIds = Product::query()
            ->whereIn('id', $sanitizedIds)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $validIdSet = array_flip($validIds);
        $reconciledIds = array_values(array_filter($sanitizedIds, fn ($id) => isset($validIdSet[$id])));

        $user = $request->user();
        if (! ($user instanceof User)) {
            $request->session()->put('favourites', $reconciledIds);

            return [
                'favourites' => $reconciledIds,
                'count' => count($reconciledIds),
            ];
        }

        // Delete favourites not in the reconciled set
        Favourite::query()
            ->where('user_id', $user->id)
            ->whereNotIn('product_id', $reconciledIds)
            ->delete();

        // Insert new favourites
        foreach ($reconciledIds as $id) {
            Favourite::firstOrCreate([
                'user_id' => $user->id,
                'product_id' => $id,
            ]);
        }

        return [
            'favourites' => $reconciledIds,
            'count' => count($reconciledIds),
        ];
    }
}
