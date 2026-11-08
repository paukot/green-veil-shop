<?php

use App\Enums\CacheKey;
use App\Enums\CacheTag;
use App\Models\Category;
use App\Models\Country;
use App\Models\DeliveryOption;
use App\Models\PaymentOption;
use App\Models\Product;
use App\Services\Cache\AppCache;

describe('AppCache and reactive invalidation', function () {
    beforeEach(function () {
        AppCache::enable();
        AppCache::flushAll();
    });

    afterEach(function () {
        AppCache::resetState();
    });

    it('bypasses cache when caching is disabled in non-production environments', function () {
        AppCache::disable();
        expect(AppCache::isEnabled())->toBeFalse();

        $invocations = 0;
        $getter = function () use (&$invocations) {
            $invocations++;

            return 'fresh_data_'.$invocations;
        };

        $val1 = AppCache::rememberForever(CacheKey::CatalogFacetsGlobal, $getter);
        expect($val1)->toBe('fresh_data_1');
        expect($invocations)->toBe(1);

        $val2 = AppCache::rememberForever(CacheKey::CatalogFacetsGlobal, $getter);
        expect($val2)->toBe('fresh_data_2');
        expect($invocations)->toBe(2);

        expect(AppCache::has(CacheKey::CatalogFacetsGlobal))->toBeFalse();
    });

    it('stores and retrieves cached data using AppCache', function () {
        $count = 0;
        $getter = function () use (&$count) {
            $count++;

            return 'cached_payload_'.$count;
        };

        $val1 = AppCache::rememberForever(CacheKey::CatalogFacetsGlobal, $getter, [CacheTag::Catalog]);
        expect($val1)->toBe('cached_payload_1');
        expect($count)->toBe(1);

        $val2 = AppCache::rememberForever(CacheKey::CatalogFacetsGlobal, $getter, [CacheTag::Catalog]);
        expect($val2)->toBe('cached_payload_1');
        expect($count)->toBe(1);

        AppCache::catalog()->flush();

        $val3 = AppCache::rememberForever(CacheKey::CatalogFacetsGlobal, $getter, [CacheTag::Catalog]);
        expect($val3)->toBe('cached_payload_2');
        expect($count)->toBe(2);
    });

    it('expires cached data after default 5 minutes TTL', function () {
        $count = 0;
        $getter = function () use (&$count) {
            $count++;

            return 'payload_'.$count;
        };

        $val1 = AppCache::remember(CacheKey::CatalogFacetsGlobal, $getter);
        expect($val1)->toBe('payload_1');
        expect($count)->toBe(1);

        $this->travel(4)->minutes();
        $val2 = AppCache::remember(CacheKey::CatalogFacetsGlobal, $getter);
        expect($val2)->toBe('payload_1');
        expect($count)->toBe(1);

        $this->travel(2)->minutes();
        $val3 = AppCache::remember(CacheKey::CatalogFacetsGlobal, $getter);
        expect($val3)->toBe('payload_2');
        expect($count)->toBe(2);
    });

    it('invalidates catalog and home caches when a Product is saved', function () {
        AppCache::rememberForever(CacheKey::CatalogFacetsGlobal, fn () => 'facets_data');
        AppCache::rememberForever(CacheKey::HomeFeaturedProducts, fn () => 'home_products');

        expect(AppCache::has(CacheKey::CatalogFacetsGlobal))->toBeTrue();
        expect(AppCache::has(CacheKey::HomeFeaturedProducts))->toBeTrue();

        $product = Product::factory()->create();

        expect(AppCache::has(CacheKey::CatalogFacetsGlobal))->toBeFalse();
        expect(AppCache::has(CacheKey::HomeFeaturedProducts))->toBeFalse();
    });

    it('invalidates category and navigation caches when a Category is saved', function () {
        AppCache::rememberForever(CacheKey::StorefrontNavigation, fn () => 'nav_data');
        AppCache::rememberForever(CacheKey::CatalogCategoriesList, fn () => 'cat_list');

        expect(AppCache::has(CacheKey::StorefrontNavigation))->toBeTrue();
        expect(AppCache::has(CacheKey::CatalogCategoriesList))->toBeTrue();

        Category::factory()->create();

        expect(AppCache::has(CacheKey::StorefrontNavigation))->toBeFalse();
        expect(AppCache::has(CacheKey::CatalogCategoriesList))->toBeFalse();
    });

    it('invalidates delivery options cache when DeliveryOption is modified', function () {
        $delivery = DeliveryOption::factory()->create();
        $options = DeliveryOption::getActiveOptions();
        expect($options)->not->toBeEmpty();
        expect(AppCache::has(CacheKey::DeliveryOptionsActive))->toBeTrue();

        $delivery->update(['price' => 12.50]);

        expect(AppCache::has(CacheKey::DeliveryOptionsActive))->toBeFalse();
    });

    it('invalidates payment options cache when PaymentOption is modified', function () {
        $payment = PaymentOption::factory()->create();
        $options = PaymentOption::getActiveOptions();
        expect($options)->not->toBeEmpty();
        expect(AppCache::has(CacheKey::PaymentOptionsActive))->toBeTrue();

        $payment->update(['name' => 'Updated Bank Wire']);

        expect(AppCache::has(CacheKey::PaymentOptionsActive))->toBeFalse();
    });

    it('invalidates country cache when Country is modified', function () {
        $country = Country::factory()->create(['code' => 'TE', 'name' => 'Testland']);
        Country::getOptionList();
        expect(AppCache::has(CacheKey::CountriesOptions))->toBeTrue();

        $country->update(['name' => 'New Testland']);

        expect(AppCache::has(CacheKey::CountriesOptions))->toBeFalse();
    });

    it('flushes caches via artisan app:cache-flush', function () {
        AppCache::rememberForever(CacheKey::CountriesOptions, fn () => 'countries', [CacheTag::Catalog]);
        AppCache::rememberForever(CacheKey::DeliveryOptionsActive, fn () => 'shipping', [CacheTag::Shipping]);

        expect(AppCache::has(CacheKey::CountriesOptions))->toBeTrue();
        expect(AppCache::has(CacheKey::DeliveryOptionsActive))->toBeTrue();

        $this->artisan('app:cache-flush --tag=shipping')
            ->assertSuccessful();

        expect(AppCache::has(CacheKey::DeliveryOptionsActive))->toBeFalse();
        expect(AppCache::has(CacheKey::CountriesOptions))->toBeTrue();

        $this->artisan('app:cache-flush --all')
            ->assertSuccessful();

        expect(AppCache::has(CacheKey::CountriesOptions))->toBeFalse();
    });
});
