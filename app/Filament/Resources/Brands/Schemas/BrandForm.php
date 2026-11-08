<?php

namespace App\Filament\Resources\Brands\Schemas;

use App\Models\Brand;
use App\Models\Country;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                                TextInput::make('slug')
                                    ->required()
                                    ->unique(Brand::class, 'slug', ignoreRecord: true)
                                    ->helperText('URL-friendly identifier'),
                                Select::make('country_of_origin')
                                    ->label('Country of Origin')
                                    ->options(fn (): array => Country::getOptionList())
                                    ->searchable()
                                    ->nullable(),
                                TextInput::make('website_url')
                                    ->label('Official Website')
                                    ->url()
                                    ->nullable()
                                    ->placeholder('https://www.example.com'),
                            ]),
                        Textarea::make('description')
                            ->label('Brand Bio & Philosophy')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Branding & Settings')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Brand Logo')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('brands')
                            ->nullable()
                            ->columnSpanFull(),
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Visible in catalog brand filters'),
                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->integer()
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }
}
