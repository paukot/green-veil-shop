<?php

namespace App\Filament\Resources\Promotions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PromotionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->placeholder('Auto'),
                TextColumn::make('discount_type')
                    ->label('Type')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->discount_type->label()),
                TextColumn::make('discount_value')
                    ->label('Value')
                    ->getStateUsing(fn ($record) => $record->discount_type->value === 'percentage'
                        ? "{$record->discount_value}%"
                        : "€{$record->discount_value}"
                    ),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->since()
                    ->placeholder('Immediately')
                    ->toggleable(),
                TextColumn::make('ends_at')
                    ->label('Ends')
                    ->since()
                    ->placeholder('Never')
                    ->toggleable(),
                TextColumn::make('times_used')
                    ->label('Uses')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                Filter::make('scheduled')
                    ->label('Scheduled')
                    ->query(fn (Builder $query) => $query->whereNotNull('starts_at')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
