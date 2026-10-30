<?php

namespace App\Filament\Resources\Promotions\Schemas;

use App\Enums\DiscountType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        Section::make('Promotion Details')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('code')
                                            ->label('Coupon Code')
                                            ->nullable()
                                            ->helperText('Leave empty for automatic/cart-wide promotions')
                                            ->columnSpanFull(),
                                        Select::make('discount_type')
                                            ->label('Discount Type')
                                            ->options(DiscountType::class)
                                            ->required()
                                            ->live(),
                                        TextInput::make('discount_value')
                                            ->label('Discount Value')
                                            ->numeric()
                                            ->required()
                                            ->suffix(fn (Get $get) => $get('discount_type') === DiscountType::Percentage->value ? '%' : '€')
                                            ->minValue(0),
                                        TextInput::make('minimum_order_amount')
                                            ->label('Minimum Order Amount')
                                            ->numeric()
                                            ->prefix('€')
                                            ->nullable(),
                                        TextInput::make('usage_limit')
                                            ->label('Usage Limit')
                                            ->numeric()
                                            ->integer()
                                            ->nullable()
                                            ->helperText('Max total uses (empty = unlimited)'),
                                        TextInput::make('usage_limit_per_user')
                                            ->label('Limit Per User')
                                            ->numeric()
                                            ->integer()
                                            ->nullable(),
                                    ]),
                            ]),

                        Section::make('Description')
                            ->schema([
                                TextInput::make('name')
                                    ->required(),
                                Textarea::make('description')
                                    ->rows(2),
                            ]),
                    ]),

                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Status & Schedule')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                                DateTimePicker::make('starts_at')
                                    ->label('Starts At')
                                    ->nullable()
                                    ->helperText('Leave empty to start immediately'),
                                DateTimePicker::make('ends_at')
                                    ->label('Ends At')
                                    ->nullable()
                                    ->helperText('Leave empty for no expiry'),
                            ]),
                        Section::make('Product Targeting')
                            ->schema([
                                Toggle::make('applies_to_all_products')
                                    ->label('Applies to All Products')
                                    ->default(true)
                                    ->live(),
                                Select::make('products')
                                    ->label('Specific Products')
                                    ->multiple()
                                    ->relationship('products', 'slug')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Get $get) => ! $get('applies_to_all_products')),
                            ]),
                    ]),
            ]);
    }
}
