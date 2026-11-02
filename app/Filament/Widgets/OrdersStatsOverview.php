<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        return ($user?->hasCategoryAccess('orders') || $user?->hasCategoryAccess('finance')) ?? false;
    }

    protected function getStats(): array
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);

        // Revenue calculations
        $currentRevenue = (float) Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->sum('total');

        $priorRevenue = (float) Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])
            ->sum('total');

        $paidOrdersCount = Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        // 7-day sparkline
        $sparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->format('Y-m-d');
            $dailySum = (float) Order::query()
                ->where('payment_status', PaymentStatus::Paid)
                ->whereDate('created_at', $day)
                ->sum('total');
            $sparkline[] = (int) round($dailySum);
        }

        // Percentage change
        if ($priorRevenue > 0) {
            $percentDiff = round((($currentRevenue - $priorRevenue) / $priorRevenue) * 100);
            $revDescription = ($percentDiff >= 0 ? "+{$percentDiff}%" : "{$percentDiff}%").' vs prior 30 days';
            $revColor = $percentDiff >= 0 ? 'success' : 'danger';
            $revIcon = $percentDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        } else {
            $revDescription = 'Past 30 days';
            $revColor = 'success';
            $revIcon = 'heroicon-m-arrow-trending-up';
        }

        // AOV calculation
        $aov = $paidOrdersCount > 0 ? ($currentRevenue / $paidOrdersCount) : 0;

        // Pending fulfillment
        $processingCount = Order::query()
            ->where('status', OrderStatus::Processing)
            ->count();

        // Checkout success rate
        $totalCheckouts = Order::query()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->where(function ($q) {
                $q->whereNotNull('stripe_session_id')
                    ->orWhere('payment_status', PaymentStatus::Paid);
            })
            ->count();

        $successRate = $totalCheckouts > 0 ? round(($paidOrdersCount / $totalCheckouts) * 100) : 100;

        return [
            Stat::make('Monthly Revenue', '€'.number_format($currentRevenue, 2))
                ->description($revDescription)
                ->descriptionIcon($revIcon)
                ->chart($sparkline)
                ->color($revColor),

            Stat::make('Average Order Value', '€'.number_format($aov, 2))
                ->description("{$paidOrdersCount} paid orders (30d)")
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),

            Stat::make('Orders to Fulfill', (string) $processingCount)
                ->description($processingCount === 0 ? 'Fulfillment up to date' : 'Awaiting packaging & shipping')
                ->descriptionIcon($processingCount > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-badge')
                ->color($processingCount > 0 ? 'warning' : 'success'),

            Stat::make('Payment Success Rate', "{$successRate}%")
                ->description($successRate >= 80 ? 'Stripe gateway healthy' : 'High decline or drop-off rate')
                ->descriptionIcon($successRate >= 80 ? 'heroicon-m-check-circle' : 'heroicon-m-shield-exclamation')
                ->color($successRate >= 80 ? 'success' : 'danger'),
        ];
    }
}
