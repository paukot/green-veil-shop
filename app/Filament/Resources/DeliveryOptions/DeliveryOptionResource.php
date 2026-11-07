<?php

namespace App\Filament\Resources\DeliveryOptions;

use App\Filament\Resources\DeliveryOptions\Pages\CreateDeliveryOption;
use App\Filament\Resources\DeliveryOptions\Pages\EditDeliveryOption;
use App\Filament\Resources\DeliveryOptions\Pages\ListDeliveryOptions;
use App\Filament\Resources\DeliveryOptions\Schemas\DeliveryOptionForm;
use App\Filament\Resources\DeliveryOptions\Tables\DeliveryOptionsTable;
use App\Models\DeliveryOption;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryOptionResource extends Resource
{
    protected static ?string $model = DeliveryOption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Delivery Options';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return DeliveryOptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryOptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryOptions::route('/'),
            'create' => CreateDeliveryOption::route('/create'),
            'edit' => EditDeliveryOption::route('/{record}/edit'),
        ];
    }
}
