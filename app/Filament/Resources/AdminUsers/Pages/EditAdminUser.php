<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\AdminUserResource;
use App\Models\AdminUser;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminUser extends EditRecord
{
    protected static string $resource = AdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(function (): bool {
                    /** @var AdminUser $record */
                    $record = $this->getRecord();

                    if ($record->id === filament()->auth()->id()) {
                        return false;
                    }

                    if ($record->isSuperAdmin()) {
                        $activeSuperAdmins = AdminUser::query()
                            ->where('is_active', true)
                            ->whereHas('adminRole', fn ($q) => $q->where('slug', 'admin'))
                            ->count();

                        return $activeSuperAdmins > 1;
                    }

                    return true;
                }),
        ];
    }
}
