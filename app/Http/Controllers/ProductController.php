<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogFilterRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SimpleCategoryResource;
use App\Http\Resources\SimpleProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\BreadcrumbService;
use App\Services\ProductCatalogService;
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

        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'name']);

        return Inertia::render('Catalog/Index', [
            'products' => ProductResource::collection($products)->additional(['total' => $products->total()]),
            'categories' => SimpleCategoryResource::collection($categories)->resolve(),
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

        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->with(['category:id,slug,name', 'brand:id,slug,name'])
            ->withCount('variants')
            ->inRandomOrder()
            ->limit(self::DEFAULT_FEATURED_PAGINATION)
            ->get();

        return Inertia::render('Catalog/Product', [
            'product' => ProductResource::make($product)->resolve(),
            'relatedProducts' => SimpleProductResource::collection($relatedProducts)->resolve(),
            'breadcrumbs' => $this->breadcrumbService->getProductBreadcrumbs($product),
        ]);
    }
}
