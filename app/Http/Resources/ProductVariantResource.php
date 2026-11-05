<?php

namespace App\Http\Resources;

use App\Models\ProductVariant;
use App\Services\StorageImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductVariant */
class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'variant_type' => $this->variant_type,
            'color_hex' => $this->color_hex,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => (float) $this->price,
            'compare_at_price' => $this->compare_at_price ? (float) $this->compare_at_price : null,
            'stock_quantity' => $this->stock_quantity,
            'track_inventory' => $this->track_inventory,
            'is_in_stock' => $this->isInStock(),
            'is_default' => $this->is_default,
            'image' => (new StorageImageService)->getImage($this->image),
            'sort_order' => $this->sort_order,
        ];
    }
}
