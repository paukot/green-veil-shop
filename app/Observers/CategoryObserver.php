<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\Cache\AppCache;

class CategoryObserver
{
    public function saved(Category $category): void
    {
        $this->flushCache();
    }

    public function deleted(Category $category): void
    {
        $this->flushCache();
    }

    public function restored(Category $category): void
    {
        $this->flushCache();
    }

    protected function flushCache(): void
    {
        AppCache::categories()->flush();
    }
}
