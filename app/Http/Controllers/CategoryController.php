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

        $filters = $request->only([
            'q',
            'brand',
            'brands',
            'min_price',
            'max_price',
            'in_stock',
            'sort',
            'skin_type',
            'skin_types',
            'hair_type',
            'hair_types',
            'effect',
            'effects',
            'ingredient',
            'ingredients',
            'claim',
            'claims',
            'classification',
            'classifications',
            'formula_type',
            'formula_types',
            'gender',
            'genders',
            'spf',
            'manufactured_in',
            'origin',
            'origins',
        ]);

        $products = $this->catalogService
            ->getFilteredProductsQuery($filters)
            ->whereIn('category_id', $categoryIds)
            ->paginate(self::DEFAULT_PAGINATION)
            ->withQueryString();

        $facets = $this->catalogService->getFilterFacets($categoryIds->all());

        return Inertia::render('Catalog/Category', [
            'category' => CategoryResource::make($category)->resolve(),
            'products' => ProductResource::collection($products)->additional(['total' => $products->total()]),
            'breadcrumbs' => $this->breadcrumbService->getCategoryBreadcrumbs($category),
            'facets' => $facets,
            'priceRange' => $facets['price_bounds'],
            'filters' => [
                'q' => $request->query('q', ''),
                'brands' => $request->query('brands', $request->query('brand', '')),
                'skin_types' => $request->query('skin_types', $request->query('skin_type', '')),
                'hair_types' => $request->query('hair_types', $request->query('hair_type', '')),
                'effects' => $request->query('effects', $request->query('effect', '')),
                'ingredients' => $request->query('ingredients', $request->query('ingredient', '')),
                'claims' => $request->query('claims', $request->query('claim', '')),
                'classifications' => $request->query('classifications', $request->query('classification', '')),
                'formula_types' => $request->query('formula_types', $request->query('formula_type', '')),
                'genders' => $request->query('genders', $request->query('gender', '')),
                'spf' => $request->query('spf', ''),
                'manufactured_in' => $request->query('manufactured_in', $request->query('origin', '')),
                'min_price' => $request->query('min_price'),
                'max_price' => $request->query('max_price'),
                'in_stock' => $request->boolean('in_stock'),
                'sort' => $request->query('sort', 'featured'),
            ],
        ]);
    }
}
