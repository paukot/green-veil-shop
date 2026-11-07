<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|array|null $columns = [
        'sm' => 2,
        'lg' => 3,
        'xl' => 3,
    ];

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        return (bool) ($user?->hasCategoryAccess('orders') || $user?->hasCategoryAccess('finance'));
    }

    protected function getStats(): array
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);

        // 1. 30-Day Paid Revenue and percentage change
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

        // Optimized single-query 7-day sparkline
        $sparklineMap = Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->where('created_at', '>=', $now->copy()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as date_key, SUM(total) as daily_total')
            ->groupBy('date_key')
            ->pluck('daily_total', 'date_key')
            ->all();

        $sparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayKey = $now->copy()->subDays($i)->format('Y-m-d');
            $sparkline[] = (float) round((float) ($sparklineMap[$dayKey] ?? 0));
        }

        // 2. Today's live sales pulse
        $todayStart = $now->copy()->startOfDay();
        $todayOrders = Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->where('created_at', '>=', $todayStart);
        $todayRevenue = (float) (clone $todayOrders)->sum('total');
        $todayCount = (clone $todayOrders)->count();

        $yesterdayStart = $now->copy()->subDay()->startOfDay();
        $yesterdayRevenue = (float) Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereBetween('created_at', [$yesterdayStart, $todayStart])
            ->sum('total');

        $todayDescription = $todayCount > 0
            ? "{$todayCount} ".($todayCount === 1 ? 'order' : 'orders').' today'
            : 'No orders yet today';
        if ($yesterdayRevenue > 0) {
            $todayDescription .= ' (€'.number_format($yesterdayRevenue, 0).' yday)';
        }

        // 3. Average Order Value
        $aov = $paidOrdersCount > 0 ? ($currentRevenue / $paidOrdersCount) : 0;

        // 4. Orders to Fulfill (Pending & Processing)
        $processingCount = Order::query()
            ->where('status', OrderStatus::Processing)
            ->count();
        $pendingCount = Order::query()
            ->where('status', OrderStatus::Pending)
            ->count();
        $fulfillTotal = $processingCount + $pendingCount;

        $fulfillDesc = $fulfillTotal === 0
            ? 'All fulfillment up to date'
            : ($processingCount > 0 && $pendingCount > 0
                ? "{$processingCount} processing, {$pendingCount} pending"
                : ($processingCount > 0 ? "{$processingCount} processing orders" : "{$pendingCount} pending orders"));

        // 5. Customer Growth (New customers registered in 30 days)
        $newCustomersCount = User::query()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();
        $priorCustomersCount = User::query()
            ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])
            ->count();

        if ($priorCustomersCount > 0) {
            $custDiff = round((($newCustomersCount - $priorCustomersCount) / $priorCustomersCount) * 100);
            $custDescription = ($custDiff >= 0 ? "+{$custDiff}%" : "{$custDiff}%").' vs prior 30 days';
            $custIcon = $custDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
            $custColor = $custDiff >= 0 ? 'success' : 'gray';
        } else {
            $custDescription = 'Registered in last 30 days';
            $custIcon = 'heroicon-m-user-group';
            $custColor = 'info';
        }

        // 6. Promotions & Marketing Impact
        $promoSavings = (float) Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->sum('discount_amount');
        $ordersWithPromo = Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('promotion_id')
            ->count();

        return [
            Stat::make('Monthly Revenue', '€'.number_format($currentRevenue, 2))
                ->description($revDescription)
                ->descriptionIcon($revIcon)
                ->chart($sparkline)
                ->color($revColor),

            Stat::make("Today's Sales", '€'.number_format($todayRevenue, 2))
                ->description($todayDescription)
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($todayRevenue > 0 ? 'success' : 'gray'),

            Stat::make('Average Order Value', '€'.number_format($aov, 2))
                ->description("{$paidOrdersCount} paid orders (30d)")
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),

            Stat::make('Orders to Fulfill', (string) $fulfillTotal)
                ->description($fulfillDesc)
                ->descriptionIcon($fulfillTotal > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-badge')
                ->color($fulfillTotal > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.orders.index')),

            Stat::make('New Customers', (string) $newCustomersCount)
                ->description($custDescription)
                ->descriptionIcon($custIcon)
                ->color($custColor)
                ->url(route('filament.admin.resources.users.index')),

            Stat::make('Promotions Savings', '€'.number_format($promoSavings, 2))
                ->description("{$ordersWithPromo} orders applied savings (30d)")
                ->descriptionIcon('heroicon-m-tag')
                ->color($promoSavings > 0 ? 'success' : 'gray')
                ->url(route('filament.admin.resources.promotions.index')),
        ];
    }
}
