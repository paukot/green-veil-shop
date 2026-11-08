<?php

namespace App\Models;

use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 * @property bool $is_shipping_available
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'code',
    'name',
    'is_active',
    'is_shipping_available',
    'sort_order',
])]
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory, SoftDeletes;

    public const CACHE_KEY_OPTIONS = 'countries.options_list';

    public const CACHE_KEY_SHIPPING = 'countries.shipping_list';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_shipping_available' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            static::clearCountryCache();
        });

        static::deleted(function (): void {
            static::clearCountryCache();
        });

        static::restored(function (): void {
            static::clearCountryCache();
        });
    }

    public static function clearCountryCache(): void
    {
        Cache::forget(self::CACHE_KEY_OPTIONS);
        Cache::forget(self::CACHE_KEY_SHIPPING);
    }

    /**
     * @param  Builder<Country>  $query
     * @return Builder<Country>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Country>  $query
     * @return Builder<Country>
     */
    public function scopeShippingAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_shipping_available', true);
    }

    /**
     * @param  Builder<Country>  $query
     * @return Builder<Country>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Returns an associative array of [code => name] for active countries.
     *
     * @return array<string, string>
     */
    public static function getOptionList(): array
    {
        /** @var array<string, string> */
        return Cache::rememberForever(self::CACHE_KEY_OPTIONS, function (): array {
            return static::query()
                ->active()
                ->ordered()
                ->pluck('name', 'code')
                ->all();
        });
    }

    /**
     * Returns a list of available shipping countries as select options [{value, label}].
     *
     * @return list<array{value: string, label: string}>
     */
    public static function getShippingOptions(): array
    {
        /** @var list<array{value: string, label: string}> */
        return Cache::rememberForever(self::CACHE_KEY_SHIPPING, function (): array {
            return static::query()
                ->shippingAvailable()
                ->ordered()
                ->get(['code', 'name'])
                ->map(fn (self $country): array => [
                    'value' => $country->code,
                    'label' => $country->name,
                ])
                ->values()
                ->all();
        });
    }

    /**
     * Resolves the country display name by ISO 2-letter code.
     */
    public static function getNameByCode(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        $options = static::getOptionList();

        return $options[strtoupper($code)] ?? strtoupper($code);
    }
}
