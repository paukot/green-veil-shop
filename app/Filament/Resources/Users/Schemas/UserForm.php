<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Details')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->helperText('Leave blank to retain current password when editing'),
                    ]),

                Section::make('Roles & Access')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_admin')
                            ->label('Administrator Access')
                            ->helperText('Grants access to the Filament management dashboard')
                            ->default(false),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->nullable()
                            ->helperText('Timestamp when customer verified their email address'),
                    ]),
            ]);
    }
}
