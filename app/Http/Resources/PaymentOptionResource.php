<?php

namespace App\Http\Resources;

use App\Models\PaymentOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentOption */
class PaymentOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'driver' => $this->driver,
            'description' => $this->description,
            'instructions' => $this->instructions,
        ];
    }
}
