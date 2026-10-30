<?php

namespace App\Filament\Resources\DeliveryOptions\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeliveryOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        TextInput::make('name')
                            ->required(),
                        Textarea::make('description')
                            ->rows(2),
                    ]),

                Section::make('Settings')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->helperText('Unique identifier, e.g. standard, express, pickup'),
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->required()
                            ->prefix('€')
                            ->minValue(0),
                        TextInput::make('free_threshold')
                            ->label('Free Shipping Threshold')
                            ->numeric()
                            ->prefix('€')
                            ->nullable()
                            ->helperText('Free shipping on orders above this amount'),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('estimated_days_min')
                                    ->label('Min Days')
                                    ->numeric()
                                    ->integer()
                                    ->nullable(),
                                TextInput::make('estimated_days_max')
                                    ->label('Max Days')
                                    ->numeric()
                                    ->integer()
                                    ->nullable(),
                            ]),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->integer()
                            ->default(0),
                    ]),
            ]);
    }
}
