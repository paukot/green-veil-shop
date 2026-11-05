<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $variant_type
 * @property string|null $color_hex
 * @property string|null $sku
 * @property string|null $barcode
 * @property float $price
 * @property float|null $compare_at_price
 * @property int $stock_quantity
 * @property bool $track_inventory
 * @property bool $is_active
 * @property bool $is_default
 * @property string|null $image
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Product|null $product
 */
#[Fillable([
    'product_id',
    'name',
    'variant_type',
    'color_hex',
    'sku',
    'barcode',
    'price',
    'compare_at_price',
    'stock_quantity',
    'track_inventory',
    'is_active',
    'is_default',
    'image',
    'sort_order',
])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @param  Builder<ProductVariant>  $query
     * @return Builder<ProductVariant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Check if the variant is currently in stock.
     */
    public function isInStock(): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        return $this->stock_quantity > 0;
    }
}
