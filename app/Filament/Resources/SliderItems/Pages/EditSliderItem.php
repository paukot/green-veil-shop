<?php

namespace App\Filament\Resources\SliderItems\Pages;

use App\Filament\Resources\SliderItems\SliderItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSliderItem extends EditRecord
{
    protected static string $resource = SliderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
