<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\SimpleCategoryResource;
use App\Http\Resources\SimpleProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\BreadcrumbService;
use App\Services\ProductCatalogService;
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

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'q',
            'category',
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
            'formula_type',
            'gender',
            'spf',
            'manufactured_in',
        ]);

        $products = $this->catalogService
            ->getFilteredProductsQuery($filters)
            ->paginate(self::DEFAULT_PAGINATION)
            ->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'name']);

        $activeProductsQuery = Product::query()->where('is_active', true);

        $priceRange = [
            'min' => (float) ($activeProductsQuery->clone()->min('price') ?? 0),
            'max' => (float) ($activeProductsQuery->clone()->max('price') ?? 100),
        ];

        return Inertia::render('Catalog/Index', [
            'products' => ProductResource::collection($products)->additional(['total' => $products->total()]),
            'categories' => SimpleCategoryResource::collection($categories)->resolve(),
            'priceRange' => $priceRange,
            'filters' => [
                'q' => $request->query('q', ''),
                'category' => $request->query('category', ''),
                'min_price' => $request->query('min_price'),
                'max_price' => $request->query('max_price'),
                'in_stock' => $request->boolean('in_stock'),
                'sort' => $request->query('sort', 'featured'),
            ],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category:id,slug,name',
            'images' => fn ($query) => $query->orderBy('sort_order'),
            'promotions' => fn ($query) => $query->where('is_active', true),
        ]);

        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->with(['category:id,slug,name'])
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
