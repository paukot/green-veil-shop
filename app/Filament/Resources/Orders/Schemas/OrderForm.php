<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Order Information')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('number')
                                            ->label('Order Number')
                                            ->disabled(),
                                        Select::make('status')
                                            ->label('Order Status')
                                            ->options(OrderStatus::class)
                                            ->required(),
                                        Select::make('payment_status')
                                            ->label('Payment Status')
                                            ->options(PaymentStatus::class)
                                            ->required(),
                                        TextInput::make('currency')
                                            ->label('Currency')
                                            ->disabled(),
                                        TextInput::make('stripe_payment_intent_id')
                                            ->label('Stripe Payment Intent')
                                            ->disabled()
                                            ->columnSpan(2),
                                    ]),
                            ]),

                        Section::make('Amounts')
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        TextInput::make('subtotal')
                                            ->label('Subtotal')
                                            ->prefix('€')
                                            ->disabled(),
                                        TextInput::make('delivery_amount')
                                            ->label('Delivery')
                                            ->prefix('€')
                                            ->disabled(),
                                        TextInput::make('discount_amount')
                                            ->label('Discount')
                                            ->prefix('€')
                                            ->disabled(),
                                        TextInput::make('tax_amount')
                                            ->label('Tax')
                                            ->prefix('€')
                                            ->disabled(),
                                        TextInput::make('total')
                                            ->label('Total')
                                            ->prefix('€')
                                            ->disabled()
                                            ->extraAttributes(['class' => 'font-bold']),
                                    ]),
                            ]),

                        Section::make('Notes')
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Order Notes')
                                    ->rows(3)
                                    ->nullable(),
                            ]),
                    ]),

                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Customer')
                            ->schema([
                                TextInput::make('customer_name')
                                    ->label('Name')
                                    ->disabled(),
                                TextInput::make('customer_email')
                                    ->label('Email')
                                    ->disabled(),
                                TextInput::make('customer_phone')
                                    ->label('Phone')
                                    ->disabled()
                                    ->placeholder('—'),
                            ]),

                        Section::make('Delivery')
                            ->schema([
                                TextInput::make('deliveryOption.code')
                                    ->label('Delivery Method')
                                    ->disabled()
                                    ->placeholder('—'),
                            ]),
                    ]),
            ]);
    }
}
