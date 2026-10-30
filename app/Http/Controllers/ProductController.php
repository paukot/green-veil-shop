<?php

namespace App\Http\Controllers;

use App\Enums\DiscountType;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        protected ProductCatalogService $catalogService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'category', 'min_price', 'max_price', 'in_stock', 'sort']);

        $products = $this->catalogService
            ->getFilteredProductsQuery($filters)
            ->paginate(12)
            ->through(fn (Product $product) => $this->catalogService->formatProductCard($product))
            ->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'products_count' => (int) $category->products_count,
            ]);

        $activeProductsQuery = Product::query()->where('is_active', true);

        $priceRange = [
            'min' => (float) ($activeProductsQuery->clone()->min('price') ?? 0),
            'max' => (float) ($activeProductsQuery->clone()->max('price') ?? 100),
        ];

        return Inertia::render('Catalog/Index', [
            'products' => $products,
            'filters' => [
                'q' => $request->query('q', ''),
                'category' => $request->query('category', ''),
                'min_price' => $request->query('min_price'),
                'max_price' => $request->query('max_price'),
                'in_stock' => $request->boolean('in_stock', false),
                'sort' => $request->query('sort', 'featured'),
            ],
            'categories' => $categories,
            'priceRange' => $priceRange,
        ]);
    }

    public function show(Request $request, Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category:id,slug,name',
            'images' => fn ($query) => $query->orderBy('sort_order'),
            'promotions' => fn ($query) => $query->where('is_active', true),
        ]);

        $activePromotions = $product->promotions
            ->filter(fn ($promo) => $promo->isCurrentlyActive())
            ->map(fn ($promo) => [
                'id' => $promo->id,
                'name' => $promo->name,
                'description' => $promo->description,
                'code' => $promo->code,
                'discount_type' => $promo->discount_type instanceof DiscountType
                    ? $promo->discount_type->value
                    : (string) $promo->discount_type,
                'discount_value' => (float) $promo->discount_value,
            ])
            ->values();

        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->with(['category:id,slug,name'])
            ->inRandomOrder()
            ->limit(4)
            ->get()
            ->map(fn (Product $related) => $this->catalogService->formatProductCard($related));

        $breadcrumbs = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => 'Products', 'url' => route('products.index')],
        ];

        if ($product->category) {
            $breadcrumbs[] = [
                'name' => $product->category->name,
                'url' => route('categories.show', $product->category),
            ];
        }

        $breadcrumbs[] = [
            'name' => $product->name,
            'url' => route('products.show', $product),
        ];

        return Inertia::render('Catalog/Product', [
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'slug' => $product->slug,
                'name' => $product->name,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'price' => (float) $product->price,
                'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
                'discount_percentage' => $product->discountPercentage(),
                'stock_quantity' => (int) $product->stock_quantity,
                'track_inventory' => (bool) $product->track_inventory,
                'is_in_stock' => $product->isInStock(),
                'main_image' => $product->main_image,
                'images' => $product->images->map(fn ($image) => [
                    'id' => $image->id,
                    'path' => $image->path,
                    'alt_text' => $image->alt_text,
                    'is_primary' => (bool) $image->is_primary,
                ]),
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'slug' => $product->category->slug,
                    'name' => $product->category->name,
                ] : null,
                'promotions' => $activePromotions,
            ],
            'relatedProducts' => $relatedProducts,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
