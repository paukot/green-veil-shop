<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\CacheTag;
use App\Models\Brand;
use App\Models\Claim;
use App\Models\Country;
use App\Models\Effect;
use App\Models\HairType;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\SkinType;
use App\Services\Cache\AppCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

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
            ->select([
                'id',
                'category_id',
                'brand_id',
                'sku',
                'slug',
                'name',
                'short_description',
                'price',
                'compare_at_price',
                'stock_quantity',
                'track_inventory',
                'is_active',
                'is_featured',
                'sort_order',
                'main_image',
                'volume',
                'unit',
                'unit_price_base_amount',
                'created_at',
            ])
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
        $normalizedCategoryIds = $categoryIds !== null
            ? array_values(array_unique(array_filter(array_map('intval', $categoryIds))))
            : null;

        if ($normalizedCategoryIds !== null) {
            sort($normalizedCategoryIds);
        }

        $cacheKey = empty($normalizedCategoryIds)
            ? CacheKey::CatalogFacetsGlobal->value
            : CacheKey::CatalogFacetsCategoryPrefix->with(implode('-', $normalizedCategoryIds));

        return AppCache::remember($cacheKey, function () use ($normalizedCategoryIds): array {
            $activeProductsConstraint = $normalizedCategoryIds
                ? fn (Builder $q) => $q->where('is_active', true)->whereIn('category_id', $normalizedCategoryIds)
                : fn (Builder $q) => $q->where('is_active', true);

            $formatTaxonomy = function (Builder $query) use ($activeProductsConstraint): array {
                /** @var Collection<int, Model> $items */
                $items = $query
                    ->whereHas('products', $activeProductsConstraint)
                    ->withCount(['products' => $activeProductsConstraint])
                    ->get(['id', 'name', 'slug']);

                return $items->map(fn ($item): array => [
                    'id' => (int) $item->getAttribute('id'),
                    'name' => (string) $item->getAttribute('name'),
                    'slug' => (string) $item->getAttribute('slug'),
                    'products_count' => (int) ($item->getAttribute('products_count') ?? 0),
                ])->values()->all();
            };

            $formatScalar = function (string $column) use ($normalizedCategoryIds): array {
                $selectExpression = match ($column) {
                    'classification' => 'classification as value, count(*) as count',
                    'formula_type' => 'formula_type as value, count(*) as count',
                    'gender' => 'gender as value, count(*) as count',
                    'spf' => 'spf as value, count(*) as count',
                    default => 'id as value, count(*) as count',
                };

                /** @var Collection<int, Product> $items */
                $items = Product::query()
                    ->where('is_active', true)
                    ->when($normalizedCategoryIds, fn (Builder $q) => $q->whereIn('category_id', $normalizedCategoryIds))
                    ->whereNotNull($column)
                    ->selectRaw($selectExpression)
                    ->groupBy($column)
                    ->get();

                return $items->map(fn ($item): array => [
                    'value' => (string) $item->getAttribute('value'),
                    'count' => (int) $item->getAttribute('count'),
                ])->values()->all();
            };

            return [
                'brands' => $formatTaxonomy(Brand::query()->orderBy('name')),
                'skin_types' => $formatTaxonomy(SkinType::query()->orderBy('sort_order')),
                'hair_types' => $formatTaxonomy(HairType::query()->orderBy('sort_order')),
                'effects' => $formatTaxonomy(Effect::query()->orderBy('sort_order')),
                'ingredients' => $formatTaxonomy(Ingredient::query()->orderBy('sort_order')),
                'claims' => $formatTaxonomy(Claim::query()->orderBy('sort_order')),
                'classifications' => $formatScalar('classification'),
                'formula_types' => $formatScalar('formula_type'),
                'genders' => $formatScalar('gender'),
                'spf' => $formatScalar('spf'),

                'origins' => Product::query()
                    ->where('is_active', true)
                    ->when($normalizedCategoryIds, fn (Builder $q) => $q->whereIn('category_id', $normalizedCategoryIds))
                    ->whereNotNull('manufactured_in')
                    ->selectRaw('manufactured_in as value, count(*) as count')
                    ->groupBy('manufactured_in')
                    ->get()
                    ->map(function (Product $item): array {
                        $val = (string) $item->getAttribute('value');

                        return [
                            'value' => $val,
                            'count' => (int) $item->getAttribute('count'),
                            'name' => Country::getNameByCode($val) ?? $val,
                        ];
                    })
                    ->values()
                    ->all(),

                'price_bounds' => [
                    'min' => (float) (Product::where('is_active', true)
                        ->when($normalizedCategoryIds, fn (Builder $q) => $q->whereIn('category_id', $normalizedCategoryIds))
                        ->min('price') ?? 0),
                    'max' => (float) (Product::where('is_active', true)
                        ->when($normalizedCategoryIds, fn (Builder $q) => $q->whereIn('category_id', $normalizedCategoryIds))
                        ->max('price') ?? 0),
                ],
            ];
        }, [CacheTag::Catalog, CacheTag::Taxonomies]);
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
