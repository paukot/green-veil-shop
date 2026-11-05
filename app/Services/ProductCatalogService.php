<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Claim;
use App\Models\Effect;
use App\Models\HairType;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\SkinType;
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
            ->with(['category:id,slug,name', 'brand:id,slug,name'])
            ->withCount('variants');

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
        $categorySlugs = $this->parseList($filters['category'] ?? null);
        if (! empty($categorySlugs)) {
            $query->whereHas('category', function (Builder $q) use ($categorySlugs) {
                $q->whereIn('slug', $categorySlugs);
            });
        }

        // Brand filter (slugs)
        $brandSlugs = $this->parseList($filters['brand'] ?? $filters['brands'] ?? null);
        if (! empty($brandSlugs)) {
            $query->whereHas('brand', function (Builder $q) use ($brandSlugs) {
                $q->whereIn('slug', $brandSlugs);
            });
        }

        // Relational Lookups
        $skinTypes = $this->parseList($filters['skin_type'] ?? $filters['skin_types'] ?? null);
        if (! empty($skinTypes)) {
            $query->whereHas('skinTypes', function (Builder $q) use ($skinTypes) {
                $q->whereIn('slug', $skinTypes);
            });
        }

        $hairTypes = $this->parseList($filters['hair_type'] ?? $filters['hair_types'] ?? null);
        if (! empty($hairTypes)) {
            $query->whereHas('hairTypes', function (Builder $q) use ($hairTypes) {
                $q->whereIn('slug', $hairTypes);
            });
        }

        $effects = $this->parseList($filters['effect'] ?? $filters['effects'] ?? null);
        if (! empty($effects)) {
            $query->whereHas('effects', function (Builder $q) use ($effects) {
                $q->whereIn('slug', $effects);
            });
        }

        $ingredients = $this->parseList($filters['ingredient'] ?? $filters['ingredients'] ?? null);
        if (! empty($ingredients)) {
            $query->whereHas('ingredients', function (Builder $q) use ($ingredients) {
                $q->whereIn('slug', $ingredients);
            });
        }

        $claims = $this->parseList($filters['claim'] ?? $filters['claims'] ?? null);
        if (! empty($claims)) {
            $query->whereHas('claims', function (Builder $q) use ($claims) {
                $q->whereIn('slug', $claims);
            });
        }

        // Scalar direct filters
        $classifications = $this->parseList($filters['classification'] ?? $filters['classifications'] ?? null);
        if (! empty($classifications)) {
            $query->whereIn('classification', $classifications);
        }

        $formulaTypes = $this->parseList($filters['formula_type'] ?? $filters['formula_types'] ?? null);
        if (! empty($formulaTypes)) {
            $query->whereIn('formula_type', $formulaTypes);
        }

        $genders = $this->parseList($filters['gender'] ?? $filters['genders'] ?? null);
        if (! empty($genders)) {
            $query->whereIn('gender', $genders);
        }

        $spf = $this->parseList($filters['spf'] ?? null);
        if (! empty($spf)) {
            $query->whereIn('spf', $spf);
        }

        $origins = $this->parseList($filters['manufactured_in'] ?? $filters['origin'] ?? $filters['origins'] ?? null);
        if (! empty($origins)) {
            $query->whereIn('manufactured_in', $origins);
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
     * Compute available filter facets with product counts.
     * Optionally scoped to specific category IDs (e.g. for a category page).
     *
     * @param  array<int>|null  $categoryIds
     * @return array<string, mixed>
     */
    public function getFilterFacets(?array $categoryIds = null): array
    {
        $activeProductsConstraint = $categoryIds
            ? fn (Builder $q) => $q->where('is_active', true)->whereIn('category_id', $categoryIds)
            : fn (Builder $q) => $q->where('is_active', true);

        return [
            'brands' => Brand::query()
                ->whereHas('products', $activeProductsConstraint)
                ->withCount(['products' => $activeProductsConstraint])
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),

            'skin_types' => SkinType::query()
                ->whereHas('products', $activeProductsConstraint)
                ->withCount(['products' => $activeProductsConstraint])
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']),

            'hair_types' => HairType::query()
                ->whereHas('products', $activeProductsConstraint)
                ->withCount(['products' => $activeProductsConstraint])
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']),

            'effects' => Effect::query()
                ->whereHas('products', $activeProductsConstraint)
                ->withCount(['products' => $activeProductsConstraint])
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']),

            'ingredients' => Ingredient::query()
                ->whereHas('products', $activeProductsConstraint)
                ->withCount(['products' => $activeProductsConstraint])
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']),

            'claims' => Claim::query()
                ->whereHas('products', $activeProductsConstraint)
                ->withCount(['products' => $activeProductsConstraint])
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']),

            'classifications' => Product::query()
                ->where('is_active', true)
                ->when($categoryIds, fn (Builder $q) => $q->whereIn('category_id', $categoryIds))
                ->whereNotNull('classification')
                ->selectRaw('classification as value, count(*) as count')
                ->groupBy('classification')
                ->get(),

            'formula_types' => Product::query()
                ->where('is_active', true)
                ->when($categoryIds, fn (Builder $q) => $q->whereIn('category_id', $categoryIds))
                ->whereNotNull('formula_type')
                ->selectRaw('formula_type as value, count(*) as count')
                ->groupBy('formula_type')
                ->get(),

            'genders' => Product::query()
                ->where('is_active', true)
                ->when($categoryIds, fn (Builder $q) => $q->whereIn('category_id', $categoryIds))
                ->whereNotNull('gender')
                ->selectRaw('gender as value, count(*) as count')
                ->groupBy('gender')
                ->get(),

            'spf' => Product::query()
                ->where('is_active', true)
                ->when($categoryIds, fn (Builder $q) => $q->whereIn('category_id', $categoryIds))
                ->whereNotNull('spf')
                ->selectRaw('spf as value, count(*) as count')
                ->groupBy('spf')
                ->get(),

            'origins' => Product::query()
                ->where('is_active', true)
                ->when($categoryIds, fn (Builder $q) => $q->whereIn('category_id', $categoryIds))
                ->whereNotNull('manufactured_in')
                ->selectRaw('manufactured_in as value, count(*) as count')
                ->groupBy('manufactured_in')
                ->get(),

            'price_bounds' => [
                'min' => (float) (Product::where('is_active', true)
                    ->when($categoryIds, fn (Builder $q) => $q->whereIn('category_id', $categoryIds))
                    ->min('price') ?? 0),
                'max' => (float) (Product::where('is_active', true)
                    ->when($categoryIds, fn (Builder $q) => $q->whereIn('category_id', $categoryIds))
                    ->max('price') ?? 0),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    protected function parseList(mixed $value): array
    {
        if (empty($value)) {
            return [];
        }

        $items = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(array_map('trim', $items)));
    }
}
