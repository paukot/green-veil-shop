<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ProductCatalogService
{
    /**
     * Build an active products query filtered and sorted by parameters.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    public function getFilteredProductsQuery(array $filters = []): Builder
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with(['category:id,slug,name']);

        // Search term
        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        // Category filter (slug or comma-separated string or array)
        if (! empty($filters['category'])) {
            $categorySlugs = is_array($filters['category'])
                ? $filters['category']
                : explode(',', (string) $filters['category']);

            $categorySlugs = array_filter(array_map('trim', $categorySlugs));

            if (! empty($categorySlugs)) {
                $query->whereHas('category', function (Builder $q) use ($categorySlugs) {
                    $q->whereIn('slug', $categorySlugs);
                });
            }
        }

        // Price range
        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        // Stock availability
        if (! empty($filters['in_stock']) && in_array($filters['in_stock'], [true, '1', 'true', 1], true)) {
            $query->where(function (Builder $q) {
                $q->where('track_inventory', false)
                    ->orWhere('stock_quantity', '>', 0);
            });
        }

        // Sorting
        $sort = $filters['sort'] ?? 'featured';
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest' => $query->latest('id'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order')->latest('id'),
        };

        return $query;
    }

    /**
     * Map a Product model instance into a clean storefront card array.
     *
     * @return array<string, mixed>
     */
    public function formatProductCard(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'name' => $product->name,
            'short_description' => $product->short_description,
            'price' => (float) $product->price,
            'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
            'discount_percentage' => $product->discountPercentage(),
            'main_image' => $product->main_image,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'slug' => $product->category->slug,
                'name' => $product->category->name,
            ] : null,
            'is_in_stock' => $product->isInStock(),
        ];
    }
}
