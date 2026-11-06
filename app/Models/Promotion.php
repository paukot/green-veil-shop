<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use Database\Factories\PromotionFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

    /** Check if a specific user is permitted to use this promotion. */
    public function canBeUsedByUser(?Authenticatable $user): bool
    {
        if (! $this->isCurrentlyActive()) {
            return false;
        }

        if ($user && $this->usage_limit_per_user !== null) {
            $userUsage = Order::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->where('promotion_id', $this->id)
                ->where('status', '!=', OrderStatus::Cancelled)
                ->count();

            if ($userUsage >= $this->usage_limit_per_user) {
                return false;
            }
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

    /**
     * Calculate discount amount for a collection of cart items and total subtotal.
     *
     * @param  Collection<int, mixed>  $cartItems
     */
    public function calculateDiscountForCart(Collection $cartItems, float $subtotal): float
    {
        if ($this->minimum_order_amount !== null && $subtotal < (float) $this->minimum_order_amount) {
            return 0.0;
        }

        if ($this->applies_to_all_products) {
            return $this->applyDiscount($subtotal);
        }

        $eligibleProductIds = $this->products()->pluck('products.id')->all();
        if (empty($eligibleProductIds)) {
            return 0.0;
        }

        $eligibleSubtotal = (float) $cartItems
            ->filter(function ($item) use ($eligibleProductIds) {
                $productId = $item->product_id ?? ($item['product_id'] ?? $item->product?->id);

                return in_array($productId, $eligibleProductIds, true);
            })
            ->sum(function ($item) {
                if (is_object($item) && method_exists($item, 'subtotal')) {
                    return $item->subtotal();
                }
                if (is_array($item) && isset($item['subtotal'])) {
                    return (float) $item['subtotal'];
                }
                $qty = is_object($item) ? ($item->quantity ?? 1) : ($item['quantity'] ?? 1);
                $price = is_object($item) ? ($item->unit_price ?? $item->product->price ?? 0) : ($item['unit_price'] ?? 0);

                return (float) $price * (int) $qty;
            });

        if ($eligibleSubtotal <= 0) {
            return 0.0;
        }

        return $this->applyDiscount($eligibleSubtotal);
    }

    /**
     * @param  Builder<Promotion>  $query
     * @return Builder<Promotion>
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    /**
     * @param  Builder<Promotion>  $query
     * @return Builder<Promotion>
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->whereRaw('LOWER(code) = ?', [strtolower(trim($code))]);
    }
}
