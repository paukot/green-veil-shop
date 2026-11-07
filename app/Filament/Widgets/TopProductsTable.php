<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopProductsTable extends BaseWidget
{
    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        return (bool) ($user?->hasCategoryAccess('shop') || $user?->hasCategoryAccess('orders'));
    }

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Bestselling Botanicals')
            ->description('Top 5 formulas by volume sold (Last 30d)')
            ->query(
                Product::query()
                    ->join('order_items', 'products.id', '=', 'order_items.product_id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('orders.payment_status', PaymentStatus::Paid)
                    ->where('orders.created_at', '>=', now()->subDays(30))
                    ->selectRaw('products.*, SUM(order_items.quantity) as units_sold, SUM(order_items.subtotal) as total_revenue')
                    ->groupBy('products.id')
                    ->orderByDesc('units_sold')
                    ->limit(5)
            )
            ->columns([
                ImageColumn::make('main_image')
                    ->label('')
                    ->square()
                    ->defaultImageUrl(asset('images/placeholder.png')),
                TextColumn::make('name')
                    ->label('Product')
                    ->limit(20)
                    ->tooltip(fn (Product $record): string => $record->name),
                TextColumn::make('units_sold')
                    ->label('Sold')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (mixed $state): string => "{$state} sold"),
                TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('EUR')
                    ->weight('bold'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Product $record): string => route('filament.admin.resources.products.edit', ['record' => $record])),
            ])
            ->paginated(false)
            ->emptyStateHeading('No Sales Data Yet')
            ->emptyStateDescription('Top selling products will appear here once orders are paid.')
            ->emptyStateIcon('heroicon-o-sparkles');
    }
}
