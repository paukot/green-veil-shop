<?php

namespace App\Actions\Orders;

use App\Actions\Checkout\ReleaseOrderReservations;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CancelOrder
{
    public function __construct(
        protected ReleaseOrderReservations $releaseOrderReservations,
    ) {}

    /** Cancel an order and give back its stock and promotion usage. */
    public function execute(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order->update(['status' => OrderStatus::Cancelled]);

            $this->releaseOrderReservations->execute($order);
        });
    }
}
