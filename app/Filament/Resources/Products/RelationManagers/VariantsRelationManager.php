<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductVariant;
use App\Services\StorageImageService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Product Variants (Shades, Volumes, Scents)';

    public const VARIANT_TYPES = [
        'color' => 'Color / Shade',
        'size' => 'Volume / Size',
        'scent' => 'Fragrance / Scent',
        'formulation' => 'Formulation',
        'other' => 'Other',
    ];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('variant_type')
                            ->label('Variant Type')
                            ->options(self::VARIANT_TYPES)
                            ->default('color')
                            ->required()
                            ->live(),
                        TextInput::make('name')
                            ->label('Variant Title / Shade Name')
                            ->placeholder('e.g. 01 Very Black, 50ml, Vanilla Dream')
                            ->required(),
                        ColorPicker::make('color_hex')
                            ->label('Swatch Color (HEX)')
                            ->visible(fn (Get $get): bool => $get('variant_type') === 'color')
                            ->nullable(),
                        TextInput::make('sku')
                            ->label('Variant SKU')
                            ->unique(ProductVariant::class, 'sku', ignoreRecord: true)
                            ->nullable(),
                        TextInput::make('barcode')
                            ->label('Variant Barcode / GTIN')
                            ->nullable(),
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->minValue(0),
                        TextInput::make('compare_at_price')
                            ->label('Compare at Price')
                            ->numeric()
                            ->prefix('€')
                            ->nullable(),
                        TextInput::make('stock_quantity')
                            ->label('Stock Quantity')
                            ->numeric()
                            ->integer()
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->integer()
                            ->default(0),
                    ]),

                FileUpload::make('image')
                    ->label('Variant Specific Image')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('product-variants')
                    ->nullable()
                    ->columnSpanFull(),

                Grid::make(3)
                    ->schema([
                        Toggle::make('is_default')
                            ->label('Default Variant')
                            ->helperText('Selected initially on product page'),
                        Toggle::make('track_inventory')
                            ->label('Track Inventory')
                            ->default(true),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->square()
                    ->imageSize(40)
                    ->getStateUsing(fn (ProductVariant $record): ?string => $record->image ? app(StorageImageService::class)->getImage($record->image) : null),
                ColorColumn::make('color_hex')
                    ->label('Swatch')
                    ->copyable(),
                TextColumn::make('name')
                    ->label('Variant')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('variant_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'color' => 'danger',
                        'size' => 'info',
                        'scent' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('price')
                    ->label('Price')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable(),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                SelectFilter::make('variant_type')
                    ->options(self::VARIANT_TYPES),
                TernaryFilter::make('is_active')->label('Active'),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Variant')
                    ->modalHeading('New Product Variant'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit Variant'),
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
