<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        protected ProductCatalogService $catalogService
    ) {}

    public function show(Request $request, Category $category): Response
    {
        abort_unless($category->is_active, 404);

        $category->load([
            'parent:id,slug,name',
            'children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->select(['id', 'parent_id', 'slug', 'name', 'image']),
        ]);

        // Include products from current category and active children
        $categoryIds = $category->children->pluck('id')->prepend($category->id);

        $filters = $request->only(['min_price', 'max_price', 'in_stock', 'sort']);

        $products = $this->catalogService
            ->getFilteredProductsQuery($filters)
            ->whereIn('category_id', $categoryIds)
            ->paginate(12)
            ->through(fn (Product $product) => $this->catalogService->formatProductCard($product))
            ->withQueryString();

        $breadcrumbs = [
            ['name' => 'Home', 'url' => '/'],
        ];

        if ($category->parent) {
            $breadcrumbs[] = [
                'name' => $category->parent->name,
                'url' => route('categories.show', $category->parent),
            ];
        }

        $breadcrumbs[] = [
            'name' => $category->name,
            'url' => route('categories.show', $category),
        ];

        return Inertia::render('Catalog/Category', [
            'category' => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'description' => $category->description,
                'image' => $category->image,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'slug' => $category->parent->slug,
                    'name' => $category->parent->name,
                ] : null,
                'children' => $category->children->map(fn (Category $child) => [
                    'id' => $child->id,
                    'slug' => $child->slug,
                    'name' => $child->name,
                    'image' => $child->image,
                ]),
            ],
            'products' => $products,
            'breadcrumbs' => $breadcrumbs,
            'filters' => [
                'min_price' => $request->query('min_price'),
                'max_price' => $request->query('max_price'),
                'in_stock' => $request->boolean('in_stock', false),
                'sort' => $request->query('sort', 'featured'),
            ],
        ]);
    }
}
