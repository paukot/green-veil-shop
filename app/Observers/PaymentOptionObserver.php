<?php

namespace App\Observers;

use App\Models\PaymentOption;
use App\Services\Cache\AppCache;

class PaymentOptionObserver
{
    public function saved(PaymentOption $paymentOption): void
    {
        $this->flushCache();
    }

    public function deleted(PaymentOption $paymentOption): void
    {
        $this->flushCache();
    }

    public function restored(PaymentOption $paymentOption): void
    {
        $this->flushCache();
    }

    protected function flushCache(): void
    {
        AppCache::settings()->flush();
    }
}
