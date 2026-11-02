<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\CartItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserCartRelationManager extends RelationManager
{
    protected static string $relationship = 'cartItems';

    protected static ?string $title = 'Active Shopping Bag';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('product.main_image')
                    ->label('')
                    ->square()
                    ->defaultImageUrl(asset('images/placeholder.png')),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->placeholder('—'),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->badge(),
                TextColumn::make('product.price')
                    ->label('Unit Price')
                    ->money('EUR'),
                TextColumn::make('subtotal')
                    ->label('Line Total')
                    ->money('EUR')
                    ->state(fn (CartItem $record): float => $record->subtotal())
                    ->weight('bold'),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since(),
            ])
            ->emptyStateHeading('Shopping Bag Empty')
            ->emptyStateDescription('This customer currently has no active items in their bag.')
            ->emptyStateIcon('heroicon-o-shopping-cart');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
