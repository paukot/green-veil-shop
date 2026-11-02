<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class InventoryAlertsTable extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Inventory Alerts (Low Stock)')
            ->description('Active products with ≤ 5 units in stock')
            ->query(
                Product::query()
                    ->where('is_active', true)
                    ->where('track_inventory', true)
                    ->where('stock_quantity', '<=', 5)
                    ->orderBy('stock_quantity', 'asc')
            )
            ->columns([
                ImageColumn::make('main_image')
                    ->label('')
                    ->square()
                    ->defaultImageUrl(asset('images/placeholder.png')),
                TextColumn::make('name')
                    ->label('Product')
                    ->limit(22)
                    ->tooltip(fn (Product $record): string => $record->name),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'warning')
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Out of stock' : "{$state} left"),
                TextColumn::make('price')
                    ->label('Price')
                    ->money('EUR'),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Restock')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Product $record): string => route('filament.admin.resources.products.edit', ['record' => $record])),
            ])
            ->paginated([5])
            ->emptyStateHeading('Inventory Healthy')
            ->emptyStateDescription('No tracked products currently have critical stock levels.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
