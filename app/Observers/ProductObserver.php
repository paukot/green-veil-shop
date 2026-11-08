<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Cache\AppCache;

class ProductObserver
{
    public function saved(Product $product): void
    {
        $this->flushCache();
    }

    public function deleted(Product $product): void
    {
        $this->flushCache();
    }

    public function restored(Product $product): void
    {
        $this->flushCache();
    }

    protected function flushCache(): void
    {
        AppCache::catalog()->flush();
        AppCache::home()->flush();
    }
}
