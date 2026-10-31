<?php

namespace App\Http\Resources;

use App\Models\DeliveryOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeliveryOption */
class DeliveryOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'estimated_delivery_label' => $this->estimatedDeliveryLabel(),
        ];
    }
}
