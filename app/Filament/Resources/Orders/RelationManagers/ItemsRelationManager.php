<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                ImageColumn::make('product_image')
                    ->label('Image')
                    ->square()
                    ->defaultImageUrl(asset('images/placeholder.png')),
                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('product_sku')
                    ->label('SKU')
                    ->toggleable(),
                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('EUR'),
                TextColumn::make('quantity')
                    ->label('Qty'),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('EUR')
                    ->weight('bold'),
            ])
            ->paginated(false);
    }

    /** Order items are read-only. */
    public function isReadOnly(): bool
    {
        return true;
    }
}
