<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\Country;
use App\Models\CustomerAddress;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserAddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Saved Shipping Addresses';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->defaultSort('is_default', 'desc')
            ->columns([
                TextColumn::make('label')
                    ->label('Label')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Phone Number')
                    ->icon('heroicon-m-phone')
                    ->placeholder('—'),
                TextColumn::make('line1')
                    ->label('Address')
                    ->description(fn (CustomerAddress $record): ?string => $record->line2)
                    ->searchable(),
                TextColumn::make('city')
                    ->label('City')
                    ->searchable(),
                TextColumn::make('postal_code')
                    ->label('Postal Code'),
                TextColumn::make('country')
                    ->label('Country')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Country::getNameByCode($state) ?? $state) : '—')
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Saved At')
                    ->dateTime('M j, Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('No Shipping Addresses Saved')
            ->emptyStateDescription('This customer has not saved any shipping addresses in their profile.')
            ->emptyStateIcon('heroicon-o-map-pin');
    }

    /**
     * Enforce strictly read-only access for customer shipping details.
     */
    public function isReadOnly(): bool
    {
        return true;
    }
}
