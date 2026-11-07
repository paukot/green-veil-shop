<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueTrendChart extends ChartWidget
{
    protected ?string $heading = 'Revenue Trend';

    protected ?string $description = 'Paid order revenue in EUR across selected timeframe';

    protected static ?int $sort = 2;

    public ?string $filter = '30d';

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

    /**
     * @return array<string, string>|null
     */
    protected function getFilters(): ?array
    {
        return [
            '7d' => 'Last 7 Days',
            '30d' => 'Last 30 Days',
            '90d' => 'Last 90 Days',
            'year' => 'This Year',
        ];
    }

    protected function getData(): array
    {
        $now = now();
        $filter = $this->filter ?? '30d';

        $labels = [];
        $data = [];

        if ($filter === 'year') {
            $startOfYear = $now->copy()->startOfYear();
            /** @var array<string, float|int|string> $dailyTotals */
            $dailyTotals = Order::query()
                ->where('payment_status', PaymentStatus::Paid)
                ->where('created_at', '>=', $startOfYear)
                ->selectRaw('DATE(created_at) as date_key, SUM(total) as daily_total')
                ->groupBy('date_key')
                ->pluck('daily_total', 'date_key')
                ->all();

            $monthlyBuckets = [];
            foreach ($dailyTotals as $date => $sum) {
                $month = substr((string) $date, 0, 7);
                $monthlyBuckets[$month] = ($monthlyBuckets[$month] ?? 0.0) + (float) $sum;
            }

            $currentMonth = (int) $now->format('n');
            for ($m = 1; $m <= $currentMonth; $m++) {
                $monthDate = $now->copy()->month($m);
                $monthKey = $monthDate->format('Y-m');
                $labels[] = $monthDate->format('M');
                $data[] = round($monthlyBuckets[$monthKey] ?? 0.0, 2);
            }
        } else {
            $days = match ($filter) {
                '7d' => 7,
                '90d' => 90,
                default => 30,
            };

            $startDate = $now->copy()->subDays($days - 1)->startOfDay();

            /** @var array<string, float|int|string> $dailyTotals */
            $dailyTotals = Order::query()
                ->where('payment_status', PaymentStatus::Paid)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date_key, SUM(total) as daily_total')
                ->groupBy('date_key')
                ->pluck('daily_total', 'date_key')
                ->all();

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i);
                $dateKey = $date->format('Y-m-d');
                $labels[] = $date->format('M j');
                $data[] = round((float) ($dailyTotals[$dateKey] ?? 0.0), 2);
            }
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
