<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogFilterRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Services\BreadcrumbService;
use App\Services\ProductCatalogService;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    const int DEFAULT_PAGINATION = 12;

    public function __construct(
        protected ProductCatalogService $catalogService,
        protected BreadcrumbService $breadcrumbService
    ) {}

    public function show(CatalogFilterRequest $request, Category $category): Response
    {
        abort_unless($category->is_active, 404);

        $category->load([
            'parent:id,slug,name',
            'children' => fn ($query) => $query->where('is_active', true)
                ->orderBy('sort_order')
                ->select(['id', 'parent_id', 'slug', 'name', 'image']),
        ]);

        // Include products from current category and active children
        $categoryIds = $category->children->pluck('id')->prepend($category->id);

        $products = $this->catalogService
            ->getFilteredProductsQuery($request->toQueryFilters())
            ->whereIn('category_id', $categoryIds)
            ->paginate(self::DEFAULT_PAGINATION)
            ->withQueryString();

        // Everything except the product page itself is lazy, so pagination (a partial reload
        // of `products`) skips the facet, breadcrumb and category work entirely.
        $facets = null;
        $resolveFacets = function () use (&$facets, $categoryIds): array {
            return $facets ??= $this->catalogService->getFilterFacets($categoryIds->all());
        };

        return Inertia::render('Catalog/Category', [
            'category' => fn (): array => CategoryResource::make($category)->resolve(),
            'products' => ProductResource::collection($products)->additional(['total' => $products->total()]),
            'breadcrumbs' => fn (): array => $this->breadcrumbService->getCategoryBreadcrumbs($category),
            'facets' => $resolveFacets,
            'priceRange' => fn (): array => $resolveFacets()['price_bounds'],
            'filters' => $request->toInertiaFilters(includeCategory: false),
        ]);
    }
}
