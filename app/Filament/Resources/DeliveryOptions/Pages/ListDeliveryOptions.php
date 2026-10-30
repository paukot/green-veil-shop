<?php

namespace App\Filament\Resources\DeliveryOptions\Pages;

use App\Filament\Resources\DeliveryOptions\DeliveryOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryOptions extends ListRecords
{
    protected static string $resource = DeliveryOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
