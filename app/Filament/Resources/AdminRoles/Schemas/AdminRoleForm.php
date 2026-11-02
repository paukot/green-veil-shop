<?php

namespace App\Filament\Resources\AdminRoles\Schemas;

use App\Models\AdminRole;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AdminRoleForm
{
    public const CATEGORY_OPTIONS = [
        '*' => 'Universal Access (*) — Unrestricted access across all panel areas',
        'shop' => 'Catalog Management (Products, Categories, Promotions)',
        'orders' => 'Orders & Fulfillment',
        'customers' => 'Customer Care (Customers, Order History, Carts)',
        'finance' => 'Finance (Payments & Transactions)',
        'marketing' => 'Marketing & Content (Hero Sliders)',
        'settings' => 'Store Settings (Payment Options, Delivery Options)',
        'system' => 'Staff & Security (Admin Users, Admin Roles, Webhook Logs)',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role Definition')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->disabled(fn (?AdminRole $record): bool => (bool) $record?->is_system)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?AdminRole $record) {
                                if (! (bool) $record?->is_system) {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(100)
                            ->unique(AdminRole::class, 'slug', ignoreRecord: true)
                            ->disabled(fn (?AdminRole $record): bool => (bool) $record?->is_system)
                            ->helperText('System identifier for the role'),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(255)
                            ->nullable(),
                        Toggle::make('is_system')
                            ->label('System Protected Role')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?AdminRole $record): bool => (bool) $record?->is_system)
                            ->helperText('This is a permanent core system role and cannot be modified or deleted.'),
                    ]),

                Section::make('Category Permissions')
                    ->columnSpan(1)
                    ->schema([
                        CheckboxList::make('permissions')
                            ->label('Permitted Categories')
                            ->options(self::CATEGORY_OPTIONS)
                            ->columns(1)
                            ->required()
                            ->disabled(fn (?AdminRole $record): bool => (bool) $record?->is_system)
                            ->helperText('Staff members assigned this role will only have access to selected categories.'),
                    ]),
            ]);
    }
}
