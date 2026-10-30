<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\FavouriteService;
use App\Services\ProductCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavouriteController extends Controller
{
    public function __construct(
        protected FavouriteService $favouriteService,
        protected ProductCatalogService $catalogService
    ) {}

    public function index(Request $request): Response
    {
        $products = $this->favouriteService->getFavouriteProducts($request);

        $formatted = $products->map(
            fn (Product $product) => $this->catalogService->formatProductCard($product)
        )->values();

        return Inertia::render('Favourites/Index', [
            'products' => $formatted,
        ]);
    }

    public function toggle(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $added = $this->favouriteService->toggle($request, $product);

        $message = $added
            ? "Saved {$product->name} to your favourites."
            : "Removed {$product->name} from your favourites.";

        return back()->with('success', $message);
    }
}
