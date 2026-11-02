<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueTrendChart extends ChartWidget
{
    protected ?string $heading = 'Revenue Trend (Last 30 Days)';

    protected ?string $description = 'Daily paid order revenue in EUR';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        return (bool) ($user?->hasCategoryAccess('finance') || $user?->hasCategoryAccess('orders'));
    }

    protected ?string $maxHeight = '500px';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    protected function getData(): array
    {
        $days = 30;
        $labels = [];
        $data = [];

        $now = now();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $labels[] = $date->format('M j');

            $dailyTotal = (float) Order::query()
                ->where('payment_status', PaymentStatus::Paid)
                ->whereDate('created_at', $dateKey)
                ->sum('total');

            $data[] = round($dailyTotal, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (€)',
                    'data' => $data,
                    'fill' => 'start',
                    'borderColor' => '#059669', // Emerald-600
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
