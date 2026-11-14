<?php

namespace App\Http\Controllers;

use App\Enums\CacheKey;
use App\Enums\CacheTag;
use App\Http\Requests\CatalogFilterRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SimpleCategoryResource;
use App\Http\Resources\SimpleProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\BreadcrumbService;
use App\Services\Cache\AppCache;
use App\Services\ProductCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    const int DEFAULT_PAGINATION = 12;

    const int DEFAULT_FEATURED_PAGINATION = 4;

    public function __construct(
        protected ProductCatalogService $catalogService,
        protected BreadcrumbService $breadcrumbService,
    ) {}

    public function index(CatalogFilterRequest $request): Response
    {
        $products = $this->catalogService
            ->getFilteredProductsQuery($request->toQueryFilters())
            ->paginate(self::DEFAULT_PAGINATION)
            ->withQueryString();

        $facets = $this->catalogService->getFilterFacets();

        $categories = AppCache::remember(
            CacheKey::CatalogCategoriesList,
            fn (): array => SimpleCategoryResource::collection(
                Category::query()
                    ->where('is_active', true)
                    ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
                    ->orderBy('sort_order')
                    ->get(['id', 'slug', 'name'])
            )->resolve(),
            [CacheTag::Categories, CacheTag::Catalog],
        );

        return Inertia::render('Catalog/Index', [
            'products' => ProductResource::collection($products)->additional(['total' => $products->total()]),
            'categories' => $categories,
            'facets' => $facets,
            'priceRange' => $facets['price_bounds'],
            'filters' => $request->toInertiaFilters(includeCategory: true),
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category:id,slug,name',
            'brand:id,slug,name,logo_path',
            'images' => fn ($query) => $query->orderBy('sort_order'),
            'promotions' => fn ($query) => $query->where('is_active', true),
            'variants' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            'skinTypes:id,name,slug',
            'hairTypes:id,name,slug',
            'effects:id,name,slug',
            'ingredients:id,name,slug',
            'claims:id,name,slug',
        ]);

        return Inertia::render('Catalog/Product', [
            'product' => ProductResource::make($product)->resolve(),
            'relatedProducts' => Inertia::defer(fn (): array => SimpleProductResource::collection(
                Product::query()
                    ->where('is_active', true)
                    ->where('id', '!=', $product->id)
                    ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
                    ->with(['category:id,slug,name', 'brand:id,slug,name'])
                    ->withCount('variants')
                    ->inRandomOrder()
                    ->limit(self::DEFAULT_FEATURED_PAGINATION)
                    ->get()
            )->resolve()),
            'breadcrumbs' => $this->breadcrumbService->getProductBreadcrumbs($product),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([
                'products' => [],
                'categories' => [],
                'total_matches' => 0,
            ]);
        }

        $productsQuery = $this->catalogService
            ->getFilteredProductsQuery(['q' => $term]);

        $totalMatches = (clone $productsQuery)->count();

        $products = $productsQuery
            ->limit(5)
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$term}%")
            ->orderBy('sort_order')
            ->limit(3)
            ->get(['id', 'slug', 'name']);

        return response()->json([
            'products' => SimpleProductResource::collection($products)->resolve(),
            'categories' => SimpleCategoryResource::collection($categories)->resolve(),
            'total_matches' => $totalMatches,
        ]);
    }
}
