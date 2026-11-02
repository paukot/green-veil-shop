<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\Favourite;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserFavouritesRelationManager extends RelationManager
{
    protected static string $relationship = 'favourites';

    protected static ?string $title = 'Wishlist / Favourites';

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
                TextColumn::make('product.price')
                    ->label('Price')
                    ->money('EUR'),
                TextColumn::make('product.stock_quantity')
                    ->label('Stock Status')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state === 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (?int $state): string => match (true) {
                        $state === null => '—',
                        $state === 0 => 'Out of stock',
                        $state <= 5 => "Low stock ({$state})",
                        default => "In stock ({$state})",
                    }),
                TextColumn::make('created_at')
                    ->label('Favorited')
                    ->since(),
            ])
            ->actions([
                Action::make('viewProduct')
                    ->label('Product')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Favourite $record): ?string => $record->product ? route('filament.admin.resources.products.edit', ['record' => $record->product]) : null)
                    ->visible(fn (Favourite $record): bool => $record->product !== null),
            ])
            ->emptyStateHeading('Wishlist Empty')
            ->emptyStateDescription('This customer has not saved any products to their wishlist.')
            ->emptyStateIcon('heroicon-o-heart');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
