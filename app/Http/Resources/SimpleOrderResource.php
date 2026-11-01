<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Services\StorageImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class SimpleOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'number' => $this->number,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'total' => (float) $this->total,
            'currency' => $this->currency,
            'placed_at' => $this->placed_at?->format('M j, Y') ?? 'Recently',
            'delivery_name' => $this->deliveryOption?->name,
            'payment_driver' => $this->paymentOption?->driver,

            'first_item_image' => (new StorageImageService)->getImage($this->items->first()?->product_image),
            'items_count' => (int) $this->items->sum('quantity'),
        ];
    }
}
