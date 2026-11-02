<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Order History';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Order #')
                    ->weight('bold')
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
                    ->label('Placed At')
                    ->dateTime('M j, Y H:i'),
            ])
            ->recordActions([
                Action::make('manage')
                    ->label('View Order')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Order $record): string => route('filament.admin.resources.orders.edit', ['record' => $record])),
            ])
            ->emptyStateHeading('No Orders Found')
            ->emptyStateDescription('This customer has not placed any orders yet.')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
