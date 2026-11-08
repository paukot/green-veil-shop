<?php

namespace App\Observers;

use App\Models\Promotion;
use App\Services\Cache\AppCache;

class PromotionObserver
{
    public function saved(Promotion $promotion): void
    {
        $this->flushCache();
    }

    public function deleted(Promotion $promotion): void
    {
        $this->flushCache();
    }

    public function restored(Promotion $promotion): void
    {
        $this->flushCache();
    }

    protected function flushCache(): void
    {
        AppCache::promotions()->flush();
    }
}
