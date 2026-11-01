<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Services\BreadcrumbService;
use App\Services\ProductCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    const int DEFAULT_PAGINATION = 12;

    public function __construct(
        protected ProductCatalogService $catalogService,
        protected BreadcrumbService $breadcrumbService
    ) {}

    public function show(Request $request, Category $category): Response
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

        $filters = $request->only(['min_price', 'max_price', 'in_stock', 'sort']);

        $products = $this->catalogService
            ->getFilteredProductsQuery($filters)
            ->whereIn('category_id', $categoryIds)
            ->paginate(self::DEFAULT_PAGINATION)
            ->withQueryString();

        return Inertia::render('Catalog/Category', [
            'category' => CategoryResource::make($category)->resolve(),
            'products' => ProductResource::collection($products)->additional(['total' => $products->total()]),
            'breadcrumbs' => $this->breadcrumbService->getCategoryBreadcrumbs($category),
            'filters' => [
                'min_price' => $request->query('min_price'),
                'max_price' => $request->query('max_price'),
                'in_stock' => $request->boolean('in_stock'),
                'sort' => $request->query('sort', 'featured'),
            ],
        ]);
    }
}
