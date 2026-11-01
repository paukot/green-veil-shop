<?php

namespace App\Http\Resources;

use App\Models\DeliveryOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeliveryOption */
class DeliveryOptionResource extends JsonResource
{
    private float $subtotal = 0.0;

    public function withSubtotal(float $subtotal): static
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'price' => (float) $this->price,
            'free_threshold' => $this->free_threshold ? (float) $this->free_threshold : null,
            'is_free' => $this->isFreeFor($this->subtotal),
            'delivery_label' => $this->estimatedDeliveryLabel(),
            'description' => $this->description,
        ];
    }
}
