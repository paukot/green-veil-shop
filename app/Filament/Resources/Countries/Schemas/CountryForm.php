<?php

namespace App\Filament\Resources\Countries\Schemas;

use App\Models\Country;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Country Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Country Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('e.g. Ireland'),
                                TextInput::make('code')
                                    ->label('ISO Alpha-2 Code')
                                    ->required()
                                    ->length(2)
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->dehydrateStateUsing(fn (?string $state): string => strtoupper($state ?? ''))
                                    ->unique(Country::class, 'code', ignoreRecord: true)
                                    ->placeholder('e.g. IE')
                                    ->helperText('Standard 2-letter uppercase ISO 3166-1 alpha-2 code.'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Available across the shop and admin panel.'),
                                Toggle::make('is_shipping_available')
                                    ->label('Shipping Available')
                                    ->default(true)
                                    ->helperText('Selectable as destination during checkout.'),
                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->integer()
                                    ->default(0)
                                    ->helperText('Lower numbers appear first.'),
                            ]),
                    ]),
            ]);
    }
}
