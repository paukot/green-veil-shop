<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'number' => $this->number,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'subtotal' => (float) $this->subtotal,
            'delivery_amount' => (float) $this->delivery_amount,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,
            'currency' => $this->currency,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'notes' => $this->notes,
            'placed_at' => $this->placed_at?->format('F j, Y, g:i a') ?? 'Recently',
            'paid_at' => $this->paid_at?->format('F j, Y, g:i a'),

            'items' => OrderItemResource::collection($this->whenLoaded('items'))->resolve(),
            'deliveryOption' => DeliveryOptionResource::make($this->whenLoaded('deliveryOption'))->resolve(),
            'paymentOption' => PaymentOptionResource::make($this->whenLoaded('paymentOption'))->resolve(),
        ];
    }
}
