<?php

namespace App\Filament\Resources\PaymentOptions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable or disable this payment method on checkout'),
                        TextInput::make('name')
                            ->label('Display Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Credit / Debit Card (Stripe)'),
                        Select::make('driver')
                            ->label('Payment Driver')
                            ->options([
                                'stripe' => 'Stripe (Credit / Debit Cards, Apple Pay, Link)',
                                'bank_transfer' => 'Bank Transfer (Offline SEPA / Wire)',
                                'cash_on_delivery' => 'Cash on Delivery (Pay upon arrival)',
                            ])
                            ->required()
                            ->default('stripe'),
                        TextInput::make('code')
                            ->label('Unique Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Internal code identifier, e.g. stripe, bank_transfer'),
                        TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->integer()
                            ->default(0),
                    ]),

                Section::make('Customer Instructions & Copy')
                    ->columnSpan(1)
                    ->schema([
                        Textarea::make('description')
                            ->label('Checkout Description')
                            ->rows(3)
                            ->placeholder('A short sentence explaining this option to the customer')
                            ->helperText('Displayed under the payment option on the checkout page'),
                        Textarea::make('instructions')
                            ->label('Payment Instructions / Bank Details')
                            ->rows(6)
                            ->placeholder("Bank: Botanical Bank of Ireland\nIBAN: IE89BOTANICAL0001234567\nBIC: BOTANIE2D\nPlease include your Order Number as payment reference.")
                            ->helperText('Displayed on the order confirmation page and in emails for offline payment options'),
                    ]),
            ]);
    }
}
