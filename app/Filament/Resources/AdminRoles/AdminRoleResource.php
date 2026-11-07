<?php

namespace App\Filament\Resources\AdminRoles;

use App\Filament\Resources\AdminRoles\Pages\CreateAdminRole;
use App\Filament\Resources\AdminRoles\Pages\EditAdminRole;
use App\Filament\Resources\AdminRoles\Pages\ListAdminRoles;
use App\Filament\Resources\AdminRoles\Schemas\AdminRoleForm;
use App\Filament\Resources\AdminRoles\Tables\AdminRolesTable;
use App\Models\AdminRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AdminRoleResource extends Resource
{
    protected static ?string $model = AdminRole::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Admin Roles';

    protected static ?string $modelLabel = 'Admin Role';

    protected static ?string $pluralModelLabel = 'Admin Roles';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return AdminRoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminRolesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminRoles::route('/'),
            'create' => CreateAdminRole::route('/create'),
            'edit' => EditAdminRole::route('/{record}/edit'),
        ];
    }
}
