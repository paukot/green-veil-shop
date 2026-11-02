<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\User;
use App\Services\CartService;
use App\Services\FavouriteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $cartSummary = app(CartService::class)->getSummary($request);
        $cartCount = $cartSummary['count'];
        $cartSubtotal = $cartSummary['subtotal'];

        $navigation = Cache::remember('storefront_category_navigation', 3600, function () {
            try {
                return Category::query()
                    ->where('is_active', true)
                    ->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->with([
                        'children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                    ])
                    ->get(['id', 'slug', 'name', 'parent_id'])
                    ->map(fn (Category $cat) => [
                        'id' => $cat->id,
                        'slug' => $cat->slug,
                        'name' => $cat->name,
                        'children' => $cat->children->map(fn (Category $child) => [
                            'id' => $child->id,
                            'slug' => $child->slug,
                            'name' => $child->name,
                        ])->values()->all(),
                    ])
                    ->values()
                    ->all();
            } catch (\Throwable) {
                return [];
            }
        });

        return [
            ...parent::share($request),
            'name' => config('app.name', 'Green Veil'),
            'demo_mode' => (bool) config('app.demo_mode', false),
            'demo_notice' => (string) config(
                'app.demo_notice',
                'This website is for demonstration purposes only. Payments are not processed and orders will not be fulfilled.'
            ),
            'navigation' => $navigation,
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user instanceof User ? $user->email_verified_at?->toISOString() : null,
                    'created_at' => $user->created_at?->toISOString(),
                ] : null,
            ],
            'cart' => [
                'count' => (int) $cartCount,
                'subtotal' => round((float) $cartSubtotal, 2),
            ],
            'favourites' => app(FavouriteService::class)->getFavouriteProductIds($request),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
