<?php

namespace App\Filament\Resources\AdminUsers\Tables;

use App\Models\AdminUser;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AdminUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn (AdminUser $record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=f43f5e&color=fff'),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('adminRole.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (AdminUser $record): string => $record->isSuperAdmin() ? 'rose' : 'info')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                IconColumn::make('has_2fa')
                    ->label('2FA Enrolled')
                    ->boolean()
                    ->getStateUsing(fn (AdminUser $record): bool => ! empty($record->app_authentication_secret))
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('admin_role_id')
                    ->label('Role')
                    ->relationship('adminRole', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(function (AdminUser $record): bool {
                        // Cannot delete self
                        if ($record->id === filament()->auth()->id()) {
                            return false;
                        }

                        // Cannot delete the last active super admin
                        if ($record->isSuperAdmin()) {
                            $activeSuperAdmins = AdminUser::query()
                                ->where('is_active', true)
                                ->whereHas('adminRole', fn ($q) => $q->where('slug', 'admin'))
                                ->count();

                            return $activeSuperAdmins > 1;
                        }

                        return true;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            $currentAdminId = filament()->auth()->id();
                            $records
                                ->filter(fn (AdminUser $admin) => $admin->id !== $currentAdminId)
                                ->filter(function (AdminUser $admin) {
                                    if ($admin->isSuperAdmin()) {
                                        $count = AdminUser::query()
                                            ->where('is_active', true)
                                            ->whereHas('adminRole', fn ($q) => $q->where('slug', 'admin'))
                                            ->count();

                                        return $count > 1;
                                    }

                                    return true;
                                })
                                ->each->delete();
                        }),
                ]),
            ]);
    }
}
