<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use App\Models\AdminUser;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Staff Information')
                    ->columnSpan(1)
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('Avatar Photo')
                            ->avatar()
                            ->image()
                            ->disk('public')
                            ->directory('avatars')
                            ->maxSize(2048),
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(AdminUser::class, 'email', ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->helperText('Leave blank to retain current password when editing'),
                    ]),

                Section::make('Role & Status')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('admin_role_id')
                            ->label('Assigned Role')
                            ->relationship('adminRole', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->disabled(function (?AdminUser $record): bool {
                                if (! $record) {
                                    return false;
                                }

                                if ($record->isSuperAdmin()) {
                                    $activeSuperAdmins = AdminUser::query()
                                        ->where('is_active', true)
                                        ->whereHas('adminRole', fn ($q) => $q->where('slug', 'admin'))
                                        ->count();

                                    return $activeSuperAdmins <= 1;
                                }

                                return false;
                            })
                            ->helperText('Determines which category areas of the panel this staff member can access.'),
                        Toggle::make('is_active')
                            ->label('Active Account')
                            ->default(true)
                            ->disabled(function (?AdminUser $record): bool {
                                if (! $record) {
                                    return false;
                                }

                                // Cannot deactivate own account
                                if ($record->id === filament()->auth()->id()) {
                                    return true;
                                }

                                // Cannot deactivate the last remaining active super admin
                                if ($record->isSuperAdmin()) {
                                    $activeSuperAdmins = AdminUser::query()
                                        ->where('is_active', true)
                                        ->whereHas('adminRole', fn ($q) => $q->where('slug', 'admin'))
                                        ->count();

                                    return $activeSuperAdmins <= 1;
                                }

                                return false;
                            })
                            ->helperText('Deactivating prevents this staff member from logging in.'),
                    ]),
            ]);
    }
}
