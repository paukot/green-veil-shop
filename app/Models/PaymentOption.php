<?php

namespace App\Models;

use App\Enums\CacheKey;
use App\Enums\CacheTag;
use App\Services\Cache\AppCache;
use Database\Factories\PaymentOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $driver
 * @property string $name
 * @property string|null $description
 * @property string|null $instructions
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'code', 'driver', 'name', 'description', 'instructions', 'is_active', 'sort_order',
])]
class PaymentOption extends Model
{
    /** @use HasFactory<PaymentOptionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
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
        $raw = AppCache::remember(CacheKey::PaymentOptionsActive, function (): array {
            return self::query()->active()->get()->toArray();
        }, [CacheTag::Settings]);

        return self::hydrate($raw);
    }
}
