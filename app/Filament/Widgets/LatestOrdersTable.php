<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrdersTable extends BaseWidget
{
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return filament()->auth()->user()?->hasCategoryAccess('orders') ?? false;
    }

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Customer Orders')
            ->description('Latest incoming orders across all payment methods')
            ->query(
                Order::query()
                    ->latest()
                    ->with(['deliveryOption', 'paymentOption'])
            )
            ->columns([
                TextColumn::make('number')
                    ->label('Order #')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Order Status')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => $state->color()),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => $state->color()),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('EUR')
                    ->weight('bold'),
                TextColumn::make('deliveryOption.code')
                    ->label('Delivery')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Placed')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('manage')
                    ->label('Manage')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Order $record): string => route('filament.admin.resources.orders.edit', ['record' => $record])),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('No Orders Yet')
            ->emptyStateDescription('Orders placed by customers will appear here in real-time.')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}
