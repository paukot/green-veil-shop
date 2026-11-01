<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Services\StorageImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class SimpleProductResource extends JsonResource
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
            'short_description' => $this->short_description,
            'price' => (float) $this->price,
            'compare_at_price' => $this->compare_at_price !== null
                ? (float) $this->compare_at_price
                : null,
            'discount_percentage' => $this->discountPercentage(),
            'main_image' => (new StorageImageService)->getImage($this->main_image),
            'category' => SimpleCategoryResource::make($this->whenLoaded('category')),
            'is_in_stock' => $this->isInStock(),

            //            'promotions' => PromotionResource::collection($this->whenLoaded('promotions')),
        ];
    }
}
