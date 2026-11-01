<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Services\StorageImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'slug' => $this->slug,

            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'main_image' => (new StorageImageService)->getImage($this->main_image),

            'price' => (float) $this->price,
            'compare_at_price' => $this->compare_at_price ? (float) $this->compare_at_price : null,
            'discount_percentage' => $this->discountPercentage(),
            'stock_quantity' => $this->stock_quantity,
            'track_inventory' => $this->track_inventory,
            'is_in_stock' => $this->isInStock(),

            'images' => ProductImageResource::collection($this->images),
            'category' => SimpleCategoryResource::make($this->whenLoaded('category')),
            'promotions' => PromotionResource::collection($this->whenLoaded('promotions')),
        ];
    }
}
