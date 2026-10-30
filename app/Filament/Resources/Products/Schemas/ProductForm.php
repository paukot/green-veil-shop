<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
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

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                        Textarea::make('short_description')
                            ->label('Short Description')
                            ->rows(2),
                        Textarea::make('description')
                            ->label('Full Description')
                            ->rows(5),
                        TextInput::make('meta_title')
                            ->label('Meta Title'),
                        Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->rows(2),
                    ]),

                Grid::make(1)
                    ->schema([
                        Section::make('Visibility')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                                Toggle::make('is_featured')
                                    ->label('Featured'),
                            ]),
                        Section::make('Main Image')
                            ->schema([
                                FileUpload::make('main_image')
                                    ->label('Main Image')
                                    ->image()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('products')
                                    ->nullable()
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Pricing')
                            ->schema([
                                TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('€')
                                    ->minValue(0),
                                TextInput::make('compare_at_price')
                                    ->label('Compare at Price')
                                    ->numeric()
                                    ->prefix('€')
                                    ->nullable()
                                    ->helperText('Original price (shows discount)'),
                            ]),
                    ]),

                Section::make('Inventory')
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->unique(Product::class, 'sku', ignoreRecord: true)
                            ->nullable(),
                        TextInput::make('stock_quantity')
                            ->label('Stock Quantity')
                            ->numeric()
                            ->integer()
                            ->default(0)
                            ->minValue(0),
                        Toggle::make('track_inventory')
                            ->label('Track Inventory')
                            ->default(true),
                    ]),

                Section::make('Organisation')
                    ->schema([
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'slug')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        TextInput::make('slug')
                            ->required()
                            ->unique(Product::class, 'slug', ignoreRecord: true)
                            ->helperText('Auto-filled from name'),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->integer()
                            ->default(0),
                    ]),
            ]);
    }
}
