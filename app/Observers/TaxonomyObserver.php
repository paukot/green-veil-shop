<?php

namespace App\Observers;

use App\Enums\CacheKey;
use App\Enums\CacheTag;
use App\Services\Cache\AppCache;
use Illuminate\Database\Eloquent\Model;

class TaxonomyObserver
{
    public function saved(Model $model): void
    {
        $this->flushCache();
    }

    public function deleted(Model $model): void
    {
        $this->flushCache();
    }

    public function restored(Model $model): void
    {
        $this->flushCache();
    }

    protected function flushCache(): void
    {
        AppCache::chain()
            ->tags([CacheTag::Taxonomies, CacheTag::Catalog])
            ->keys([CacheKey::CatalogFacetsGlobal])
            ->patterns(['catalog.facets.*'])
            ->flush();
    }
}
