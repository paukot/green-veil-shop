<?php

namespace App\Payments\Offline;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTOs\CheckoutResult;
use App\Payments\DTOs\RefundResult;
use Illuminate\Database\Eloquent\Model;

class OfflineGateway implements PaymentGateway
{
    public function __construct(
        protected string $driver = 'offline'
    ) {}

    public function createCheckout(Order $order): CheckoutResult
    {
        Payment::create([
            'order_id' => $order->id,
            'driver' => $this->driver,
            'status' => PaymentStatus::Unpaid,
            'amount' => $order->total,
            'currency' => $order->currency,
        ]);

        if ($this->driver === 'cod') {
            $order->update(['status' => OrderStatus::Processing]);
        }

        return CheckoutResult::completedDirectly();
    }

    public function refund(Model $payment, ?int $amountInCents = null, ?string $reason = null): RefundResult
    {
        return RefundResult::success('offline_refund_'.uniqid());
    }
}
