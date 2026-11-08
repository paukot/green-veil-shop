<?php

namespace App\Services\Cache;

use App\Enums\CacheKey;
use App\Enums\CacheTag;
use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

class AppCache
{
    protected static ?bool $enabledOverride = null;

    /**
     * Determine if application caching is currently enabled.
     * Caching only runs in production unless explicitly enabled.
     */
    public static function isEnabled(): bool
    {
        if (static::$enabledOverride !== null) {
            return static::$enabledOverride;
        }

        return app()->isProduction() || (bool) config('cache.storefront_enabled', false);
    }

    /**
     * Explicitly enable caching (e.g. for testing or CLI override).
     */
    public static function enable(): void
    {
        static::$enabledOverride = true;
    }

    /**
     * Explicitly disable caching.
     */
    public static function disable(): void
    {
        static::$enabledOverride = false;
    }

    /**
     * Reset the manual enable/disable override.
     */
    public static function resetState(): void
    {
        static::$enabledOverride = null;
    }

    /**
     * Default TTL for cached entries (5 minutes = 300 seconds).
     * Prevents stale data from remaining indefinitely in all cases.
     */
    public const int DEFAULT_TTL_SECONDS = 300;

    /**
     * Get the configured storefront cache TTL in seconds.
     */
    public static function getTtl(): int
    {
        return (int) config('cache.storefront_ttl', self::DEFAULT_TTL_SECONDS);
    }

    /**
     * Retrieve an item from the cache, or store it for a bounded duration (defaults to 5 minutes).
     * Bypasses caching and executes callback directly when caching is disabled.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @param  list<CacheTag|string>|CacheTag|string  $tags
     * @param  int|null  $ttlSeconds  Cache duration in seconds (defaults to 5 minutes)
     * @return T
     */
    public static function remember(CacheKey|string $key, Closure $callback, array|CacheTag|string $tags = [], ?int $ttlSeconds = null): mixed
    {
        if (! self::isEnabled()) {
            return $callback();
        }

        $ttl = $ttlSeconds ?? self::getTtl();
        $resolvedKey = $key instanceof CacheKey ? $key->value : $key;
        $resolvedTags = self::resolveTagsForKey($key, $tags);

        if (! empty($resolvedTags)) {
            try {
                return Cache::tags($resolvedTags)->remember($resolvedKey, $ttl, $callback);
            } catch (Throwable) {
                return Cache::remember($resolvedKey, $ttl, $callback);
            }
        }

        return Cache::remember($resolvedKey, $ttl, $callback);
    }

    /**
     * Alias for remember() bounded to the default TTL (5 minutes).
     * Ensures data is never retained indefinitely in any case.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @param  list<CacheTag|string>|CacheTag|string  $tags
     * @return T
     */
    public static function rememberForever(CacheKey|string $key, Closure $callback, array|CacheTag|string $tags = [], ?int $ttlSeconds = null): mixed
    {
        return self::remember($key, $callback, $tags, $ttlSeconds);
    }

    /**
     * Retrieve an item from the cache.
     *
     * @param  list<CacheTag|string>|CacheTag|string  $tags
     */
    public static function get(CacheKey|string $key, mixed $default = null, array|CacheTag|string $tags = []): mixed
    {
        if (! self::isEnabled()) {
            return value($default);
        }

        $resolvedKey = $key instanceof CacheKey ? $key->value : $key;
        $resolvedTags = self::resolveTagsForKey($key, $tags);

        if (! empty($resolvedTags)) {
            try {
                return Cache::tags($resolvedTags)->get($resolvedKey, $default);
            } catch (Throwable) {
                return Cache::get($resolvedKey, $default);
            }
        }

        return Cache::get($resolvedKey, $default);
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  list<CacheTag|string>|CacheTag|string  $tags
     */
    public static function has(CacheKey|string $key, array|CacheTag|string $tags = []): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        $resolvedKey = $key instanceof CacheKey ? $key->value : $key;
        $resolvedTags = self::resolveTagsForKey($key, $tags);

        if (! empty($resolvedTags)) {
            try {
                return Cache::tags($resolvedTags)->has($resolvedKey);
            } catch (Throwable) {
                return Cache::has($resolvedKey);
            }
        }

        return Cache::has($resolvedKey);
    }

    /**
     * Remove an item from the cache across tagged and root stores.
     *
     * @param  list<CacheTag|string>|CacheTag|string  $tags
     */
    public static function forget(CacheKey|string $key, array|CacheTag|string $tags = []): bool
    {
        $resolvedKey = $key instanceof CacheKey ? $key->value : $key;
        $resolvedTags = self::resolveTagsForKey($key, $tags);

        if (! empty($resolvedTags)) {
            try {
                Cache::tags($resolvedTags)->forget($resolvedKey);
            } catch (Throwable) {
                // Ignore
            }
        }

        return Cache::forget($resolvedKey);
    }

    /**
     * Start a new fluent cache flush chain.
     */
    public static function chain(): CacheFlushChain
    {
        return new CacheFlushChain;
    }

    /**
     * Start a fluent flush chain with initial tags.
     *
     * @param  list<CacheTag|string>|CacheTag|string  $tags
     */
    public static function tags(array|CacheTag|string $tags): CacheFlushChain
    {
        return self::chain()->tags($tags);
    }

    /**
     * Start a fluent flush chain with initial keys.
     *
     * @param  list<CacheKey|string>|CacheKey|string  $keys
     */
    public static function keys(array|CacheKey|string $keys): CacheFlushChain
    {
        return self::chain()->keys($keys);
    }

    /**
     * Flush an entire domain tag.
     */
    public static function flushTag(CacheTag|string $tag): void
    {
        self::chain()->tags($tag)->flush();
    }

    /**
     * Flush catalog and product related caches.
     */
    public static function catalog(): CacheFlushChain
    {
        return self::chain()
            ->tags([CacheTag::Catalog, CacheTag::Products])
            ->patterns(['catalog.*'])
            ->keys([CacheKey::CatalogFacetsGlobal]);
    }

    /**
     * Flush home page and showcase caches.
     */
    public static function home(): CacheFlushChain
    {
        return self::chain()
            ->tags([CacheTag::Home])
            ->patterns(['home.*'])
            ->keys([
                CacheKey::HomeHeroSlides,
                CacheKey::HomePromotions,
                CacheKey::HomeFeaturedCategories,
                CacheKey::HomeFeaturedProducts,
            ]);
    }

    /**
     * Flush delivery and shipping caches.
     */
    public static function shipping(): CacheFlushChain
    {
        return self::chain()
            ->tags([CacheTag::Shipping])
            ->keys([CacheKey::DeliveryOptionsActive]);
    }

    /**
     * Flush payment and store settings caches.
     */
    public static function settings(): CacheFlushChain
    {
        return self::chain()
            ->tags([CacheTag::Settings])
            ->keys([CacheKey::PaymentOptionsActive]);
    }

    /**
     * Flush country caches.
     */
    public static function countries(): CacheFlushChain
    {
        return self::chain()
            ->tags([CacheTag::Catalog])
            ->keys([CacheKey::CountriesOptions, CacheKey::CountriesShipping]);
    }

    /**
     * Flush categories and navigation caches.
     */
    public static function categories(): CacheFlushChain
    {
        return self::chain()
            ->tags([CacheTag::Categories, CacheTag::Catalog, CacheTag::Home])
            ->patterns(['storefront.*'])
            ->keys([
                CacheKey::StorefrontNavigation,
                CacheKey::HomeFeaturedCategories,
                CacheKey::CatalogFacetsGlobal,
                CacheKey::CatalogCategoriesList,
            ]);
    }

    /**
     * Flush promotions caches.
     */
    public static function promotions(): CacheFlushChain
    {
        return self::chain()
            ->tags([CacheTag::Promotions, CacheTag::Home])
            ->keys([CacheKey::HomePromotions]);
    }

    /**
     * Flush all application caches safely.
     */
    public static function flushAll(): void
    {
        self::chain()
            ->tags(CacheTag::cases())
            ->patterns(['catalog.*', 'home.*', 'countries.*', 'shipping.*', 'settings.*', 'storefront.*'])
            ->keys([
                CacheKey::CountriesOptions,
                CacheKey::CountriesShipping,
                CacheKey::DeliveryOptionsActive,
                CacheKey::PaymentOptionsActive,
                CacheKey::StorefrontNavigation,
                CacheKey::HomeHeroSlides,
                CacheKey::HomePromotions,
                CacheKey::HomeFeaturedCategories,
                CacheKey::HomeFeaturedProducts,
                CacheKey::CatalogFacetsGlobal,
                CacheKey::CatalogCategoriesList,
            ])
            ->flush();
    }

    /**
     * Determine default tags assigned to a cache key.
     *
     * @return list<CacheTag>
     */
    public static function getTagsForCacheKey(CacheKey|string $key): array
    {
        $keyEnum = $key instanceof CacheKey ? $key : CacheKey::tryFrom($key);
        if (! $keyEnum) {
            return [];
        }

        return match ($keyEnum) {
            CacheKey::CountriesOptions,
            CacheKey::CountriesShipping => [CacheTag::Catalog],
            CacheKey::DeliveryOptionsActive => [CacheTag::Shipping],
            CacheKey::PaymentOptionsActive => [CacheTag::Settings],
            CacheKey::StorefrontNavigation => [CacheTag::Categories, CacheTag::Catalog],
            CacheKey::CatalogCategoriesList => [CacheTag::Categories, CacheTag::Catalog],
            CacheKey::HomeHeroSlides => [CacheTag::Home],
            CacheKey::HomePromotions => [CacheTag::Home, CacheTag::Promotions],
            CacheKey::HomeFeaturedCategories => [CacheTag::Home, CacheTag::Categories],
            CacheKey::HomeFeaturedProducts => [CacheTag::Home, CacheTag::Products],
            CacheKey::CatalogFacetsGlobal,
            CacheKey::CatalogFacetsCategoryPrefix => [CacheTag::Catalog, CacheTag::Taxonomies],
        };
    }

    /**
     * @param  list<CacheTag|string>|CacheTag|string  $tags
     * @return list<string>
     */
    protected static function resolveTagStrings(array|CacheTag|string $tags): array
    {
        if (empty($tags)) {
            return [];
        }

        $list = is_array($tags) ? $tags : [$tags];
        $result = [];

        foreach ($list as $tag) {
            $result[] = $tag instanceof CacheTag ? $tag->value : (string) $tag;
        }

        return array_values(array_unique($result));
    }

    /**
     * @param  list<CacheTag|string>|CacheTag|string  $tags
     * @return list<string>
     */
    protected static function resolveTagsForKey(CacheKey|string $key, array|CacheTag|string $tags): array
    {
        if (! empty($tags)) {
            return self::resolveTagStrings($tags);
        }

        $defaultTags = self::getTagsForCacheKey($key);

        return self::resolveTagStrings($defaultTags);
    }
}
