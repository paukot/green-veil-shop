<?php

namespace App\Observers;

use App\Models\DeliveryOption;
use App\Services\Cache\AppCache;

class DeliveryOptionObserver
{
    public function saved(DeliveryOption $deliveryOption): void
    {
        $this->flushCache();
    }

    public function deleted(DeliveryOption $deliveryOption): void
    {
        $this->flushCache();
    }

    public function restored(DeliveryOption $deliveryOption): void
    {
        $this->flushCache();
    }

    protected function flushCache(): void
    {
        AppCache::shipping()->flush();
    }
}
