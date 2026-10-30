<?php

namespace App\Models;

use App\Enums\DiscountType;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $code
 * @property DiscountType $discount_type
 * @property float $discount_value
 * @property float|null $minimum_order_amount
 * @property int|null $usage_limit
 * @property int|null $usage_limit_per_user
 * @property int $times_used
 * @property bool $is_active
 * @property bool $applies_to_all_products
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'code', 'discount_type', 'discount_value', 'minimum_order_amount',
    'usage_limit', 'usage_limit_per_user', 'times_used', 'is_active',
    'applies_to_all_products', 'starts_at', 'ends_at', 'name', 'description',
])]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'applies_to_all_products' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_product');
    }

    /** Whether this promotion is currently active and within its schedule window. */
    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /** Calculate the discounted amount for a given price. */
    public function applyDiscount(float $price): float
    {
        return match ($this->discount_type) {
            DiscountType::Percentage => round($price * ($this->discount_value / 100), 2),
            DiscountType::FixedAmount => min((float) $this->discount_value, $price),
        };
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
