<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $category_id
 * @property int|null $brand_id
 * @property string|null $series
 * @property string|null $sku
 * @property string|null $barcode
 * @property string $slug
 * @property float $price
 * @property float|null $compare_at_price
 * @property float|null $volume
 * @property string $unit
 * @property int $unit_price_base_amount
 * @property string|null $classification
 * @property string $gender
 * @property string|null $age_range
 * @property string|null $formula_type
 * @property string|null $spf
 * @property array<string, string>|null $custom_characteristics
 * @property string|null $inci_list
 * @property string|null $how_to_use
 * @property string|null $warnings
 * @property string|null $manufactured_in
 * @property int|null $pao_months
 * @property string|null $responsible_person
 * @property int $stock_quantity
 * @property bool $track_inventory
 * @property bool $is_active
 * @property bool $is_featured
 * @property int $sort_order
 * @property string|null $main_image
 * @property string $name
 * @property string|null $short_description
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category|null $category
 * @property-read Brand|null $brand
 * @property-read Collection<int, SkinType> $skinTypes
 * @property-read Collection<int, HairType> $hairTypes
 * @property-read Collection<int, Effect> $effects
 * @property-read Collection<int, Ingredient> $ingredients
 * @property-read Collection<int, Claim> $claims
 */
#[Fillable([
    'category_id',
    'brand_id',
    'series',
    'sku',
    'barcode',
    'slug',
    'price',
    'compare_at_price',
    'volume',
    'unit',
    'unit_price_base_amount',
    'classification',
    'gender',
    'age_range',
    'formula_type',
    'spf',
    'custom_characteristics',
    'inci_list',
    'how_to_use',
    'warnings',
    'manufactured_in',
    'pao_months',
    'responsible_person',
    'stock_quantity',
    'track_inventory',
    'is_active',
    'is_featured',
    'sort_order',
    'main_image',
    'name',
    'short_description',
    'description',
    'meta_title',
    'meta_description',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'volume' => 'decimal:2',
            'unit_price_base_amount' => 'integer',
            'pao_months' => 'integer',
            'custom_characteristics' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'track_inventory' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return HasMany<ProductImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    /** @return BelongsToMany<Promotion, $this> */
    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_product');
    }

    /** @return BelongsToMany<SkinType, $this> */
    public function skinTypes(): BelongsToMany
    {
        return $this->belongsToMany(SkinType::class, 'product_skin_type')->orderBy('sort_order');
    }

    /** @return BelongsToMany<HairType, $this> */
    public function hairTypes(): BelongsToMany
    {
        return $this->belongsToMany(HairType::class, 'product_hair_type')->orderBy('sort_order');
    }

    /** @return BelongsToMany<Effect, $this> */
    public function effects(): BelongsToMany
    {
        return $this->belongsToMany(Effect::class, 'product_effect')->orderBy('sort_order');
    }

    /** @return BelongsToMany<Ingredient, $this> */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredient')->orderBy('sort_order');
    }

    /** @return BelongsToMany<Claim, $this> */
    public function claims(): BelongsToMany
    {
        return $this->belongsToMany(Claim::class, 'product_claim')->orderBy('sort_order');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /** Whether the product is in stock. */
    public function isInStock(): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    /** The discount percentage vs. compare_at_price, if any. */
    public function discountPercentage(): ?int
    {
        if ($this->compare_at_price === null || $this->compare_at_price <= $this->price) {
            return null;
        }

        return (int) round((1 - $this->price / $this->compare_at_price) * 100);
    }

    /**
     * Calculate unit price per base amount (e.g. price per 100 ml or per 100 g)
     * as required by EU Directive 98/6/EC.
     */
    public function calculateUnitPrice(): ?float
    {
        if (! $this->volume || (float) $this->volume <= 0 || ! $this->price) {
            return null;
        }

        $baseAmount = $this->unit_price_base_amount ?: 100;

        return round(((float) $this->price / (float) $this->volume) * $baseAmount, 2);
    }

    /**
     * Formatted unit price string (e.g. "427.64 € / 100 ml").
     */
    public function formattedUnitPrice(): ?string
    {
        $unitPrice = $this->calculateUnitPrice();
        if ($unitPrice === null) {
            return null;
        }

        $base = $this->unit_price_base_amount ?: 100;
        $unit = $this->unit ?: 'ml';

        return number_format($unitPrice, 2).' € / '.$base.' '.$unit;
    }
}
