<?php

namespace App\Filament\Resources\PaymentOptions;

use App\Filament\Resources\PaymentOptions\Pages\CreatePaymentOption;
use App\Filament\Resources\PaymentOptions\Pages\EditPaymentOption;
use App\Filament\Resources\PaymentOptions\Pages\ListPaymentOptions;
use App\Filament\Resources\PaymentOptions\Schemas\PaymentOptionForm;
use App\Filament\Resources\PaymentOptions\Tables\PaymentOptionsTable;
use App\Models\PaymentOption;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentOptionResource extends Resource
{
    protected static ?string $model = PaymentOption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Shop Settings';

    protected static ?string $navigationLabel = 'Payment Options';

    public static function form(Schema $schema): Schema
    {
        return PaymentOptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentOptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentOptions::route('/'),
            'create' => CreatePaymentOption::route('/create'),
            'edit' => EditPaymentOption::route('/{record}/edit'),
        ];
    }
}
