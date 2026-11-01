<?php

namespace App\Console\Commands;

use App\Actions\Checkout\ReleaseOrderStock;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SweepStaleOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:sweep-stale 
                            {--minutes=60 : Minutes after which pending Stripe orders expire}
                            {--bank-days=7 : Days after which pending bank transfer orders expire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel stale unpaid orders and release reserved inventory back to stock';

    public function handle(ReleaseOrderStock $releaseOrderStock): int
    {
        $minutes = max(15, (int) $this->option('minutes'));
        $stripeCutoff = now()->subMinutes($minutes);

        $staleStripeOrders = Order::query()
            ->where('status', OrderStatus::PendingPayment)
            ->where('payment_status', PaymentStatus::Unpaid)
            ->where('placed_at', '<', $stripeCutoff)
            ->where(function ($query) {
                $query->whereHas('paymentOption', fn ($q) => $q->where('driver', 'stripe'))
                    ->orWhereNull('payment_option_id');
            })
            ->with(['items', 'paymentOption'])
            ->get();

        $bankDays = max(1, (int) $this->option('bank-days'));
        $bankCutoff = now()->subDays($bankDays);

        $staleBankOrders = Order::query()
            ->where('status', OrderStatus::PendingPayment)
            ->where('payment_status', PaymentStatus::Unpaid)
            ->where('placed_at', '<', $bankCutoff)
            ->whereHas('paymentOption', fn ($q) => $q->where('driver', 'bank_transfer'))
            ->with(['items', 'paymentOption'])
            ->get();

        $staleOrders = $staleStripeOrders->merge($staleBankOrders)->unique('id');

        if ($staleOrders->isEmpty()) {
            $this->info('No stale unpaid orders found older than the configured thresholds.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($staleOrders as $order) {
            DB::transaction(function () use ($order, $releaseOrderStock): void {
                /** @var Order|null $lockedOrder */
                $lockedOrder = Order::query()->where('id', $order->id)->lockForUpdate()->first();

                if (! $lockedOrder || $lockedOrder->payment_status === PaymentStatus::Paid) {
                    return;
                }

                $driver = $lockedOrder->paymentOption->driver ?? 'stripe';

                $lockedOrder->update([
                    'status' => OrderStatus::Cancelled,
                    'payment_status' => PaymentStatus::Expired,
                ]);

                Payment::updateOrCreate(
                    ['order_id' => $lockedOrder->id, 'driver' => $driver],
                    [
                        'status' => PaymentStatus::Expired,
                        'failure_reason' => 'Automatically cancelled by stale order sweep',
                        'amount' => $lockedOrder->total,
                        'currency' => $lockedOrder->currency,
                    ]
                );

                $releaseOrderStock->execute($lockedOrder);

                Log::info("Order {$lockedOrder->number} ({$lockedOrder->id}) cancelled by sweep-stale. Reserved inventory released.");
            });

            $count++;
        }

        $this->info("Successfully swept {$count} stale order(s) and released inventory.");

        return self::SUCCESS;
    }
}
