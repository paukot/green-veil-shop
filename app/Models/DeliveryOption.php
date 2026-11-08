<?php

namespace App\Models;

use App\Enums\CacheKey;
use App\Enums\CacheTag;
use App\Services\Cache\AppCache;
use Database\Factories\DeliveryOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property float $price
 * @property float|null $free_threshold
 * @property int|null $estimated_days_min
 * @property int|null $estimated_days_max
 * @property bool $is_active
 * @property int $sort_order
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'code', 'price', 'free_threshold', 'estimated_days_min',
    'estimated_days_max', 'is_active', 'sort_order', 'name', 'description',
])]
class DeliveryOption extends Model
{
    /** @use HasFactory<DeliveryOptionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'free_threshold' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<DeliveryOption>  $query
     * @return Builder<DeliveryOption>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * @return Collection<int, self>
     */
    public static function getActiveOptions(): Collection
    {
        /** @var list<array<string, mixed>> $raw */
        $raw = AppCache::remember(CacheKey::DeliveryOptionsActive, function (): array {
            return self::query()->active()->get()->toArray();
        }, [CacheTag::Shipping]);

        return self::hydrate($raw);
    }

    /** Delivery label including estimated days. */
    public function estimatedDeliveryLabel(): string
    {
        if ($this->estimated_days_min && $this->estimated_days_max) {
            return "{$this->estimated_days_min}–{$this->estimated_days_max} days";
        }

        if ($this->estimated_days_min) {
            return "from {$this->estimated_days_min} days";
        }

        return '';
    }

    public function isFreeFor(float $subtotal): bool
    {
        return $this->free_threshold !== null
            && $subtotal >= (float) $this->free_threshold;
    }
}
