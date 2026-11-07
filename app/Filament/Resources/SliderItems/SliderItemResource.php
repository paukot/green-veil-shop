<?php

namespace App\Filament\Resources\SliderItems;

use App\Filament\Resources\SliderItems\Pages\CreateSliderItem;
use App\Filament\Resources\SliderItems\Pages\EditSliderItem;
use App\Filament\Resources\SliderItems\Pages\ListSliderItems;
use App\Filament\Resources\SliderItems\Schemas\SliderItemForm;
use App\Filament\Resources\SliderItems\Tables\SliderItemsTable;
use App\Models\SliderItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SliderItemResource extends Resource
{
    protected static ?string $model = SliderItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Shop';

    protected static ?string $navigationLabel = 'Homepage Banners';

    protected static ?string $modelLabel = 'Homepage Banner';

    protected static ?string $pluralModelLabel = 'Homepage Banners';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return SliderItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SliderItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSliderItems::route('/'),
            'create' => CreateSliderItem::route('/create'),
            'edit' => EditSliderItem::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
