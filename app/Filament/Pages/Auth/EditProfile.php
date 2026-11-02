<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('avatar_url')
                    ->label('Avatar Photo')
                    ->avatar()
                    ->image()
                    ->disk('public')
                    ->directory('avatars')
                    ->maxSize(2048),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                TextEntry::make('role_info')
                    ->label('Assigned Role')
                    ->state(function (): HtmlString {
                        $user = $this->getUser();
                        $roleName = e($user->adminRole?->name ?? 'No Role');
                        $perms = e(implode(', ', $user->adminRole?->permissions ?? ['none']));

                        return new HtmlString("<span class=\"font-semibold text-primary-600 dark:text-primary-400\">{$roleName}</span> <span class=\"text-xs text-gray-500 dark:text-gray-400\">({$perms})</span>");
                    }),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }
}
