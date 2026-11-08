<?php

namespace App\Observers;

use App\Models\SliderItem;
use App\Services\Cache\AppCache;

class SliderItemObserver
{
    public function saved(SliderItem $sliderItem): void
    {
        $this->flushCache();
    }

    public function deleted(SliderItem $sliderItem): void
    {
        $this->flushCache();
    }

    public function restored(SliderItem $sliderItem): void
    {
        $this->flushCache();
    }

    protected function flushCache(): void
    {
        AppCache::home()->flush();
    }
}
