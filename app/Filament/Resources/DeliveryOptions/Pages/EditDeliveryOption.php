<?php

namespace App\Filament\Resources\DeliveryOptions\Pages;

use App\Filament\Resources\DeliveryOptions\DeliveryOptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryOption extends EditRecord
{
    protected static string $resource = DeliveryOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
