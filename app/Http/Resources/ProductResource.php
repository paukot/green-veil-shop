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
        $imageService = new StorageImageService;

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'slug' => $this->slug,

            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'main_image' => $imageService->getImage($this->main_image),

            'price' => (float) $this->price,
            'compare_at_price' => $this->compare_at_price ? (float) $this->compare_at_price : null,
            'discount_percentage' => $this->discountPercentage(),
            'stock_quantity' => $this->stock_quantity,
            'track_inventory' => $this->track_inventory,
            'is_in_stock' => $this->isInStock(),

            // Volume & EU Unit Pricing (Directive 98/6/EC)
            'volume' => $this->volume ? (float) $this->volume : null,
            'unit' => $this->unit,
            'unit_price_base_amount' => $this->unit_price_base_amount,
            'formatted_unit_price' => $this->formattedUnitPrice(),

            // Cosmetic Classification & Demographics
            'classification' => $this->classification,
            'gender' => $this->gender,
            'age_range' => $this->age_range,
            'formula_type' => $this->formula_type,
            'spf' => $this->spf,

            // Origin & Regulatory
            'manufactured_in' => $this->manufactured_in,
            'pao_months' => $this->pao_months,
            'responsible_person' => $this->responsible_person,

            // Editorial & Regulatory Content
            'inci_list' => $this->inci_list,
            'how_to_use' => $this->how_to_use,
            'warnings' => $this->warnings,
            'custom_characteristics' => $this->custom_characteristics,

            // Brand & Series
            'brand' => $this->when($this->relationLoaded('brand') && $this->brand, fn () => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'logo_path' => $imageService->getImage($this->brand->logo_path),
            ]),
            'series' => $this->series,

            // Relationships
            'images' => ProductImageResource::collection($this->images),
            'category' => SimpleCategoryResource::make($this->whenLoaded('category')),
            'promotions' => PromotionResource::collection($this->whenLoaded('promotions')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),

            // Cosmetic Attribute Lookups
            'skin_types' => $this->whenLoaded('skinTypes', fn () => $this->skinTypes->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
            ])->values()),
            'hair_types' => $this->whenLoaded('hairTypes', fn () => $this->hairTypes->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
            ])->values()),
            'effects' => $this->whenLoaded('effects', fn () => $this->effects->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
            ])->values()),
            'ingredients' => $this->whenLoaded('ingredients', fn () => $this->ingredients->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
            ])->values()),
            'claims' => $this->whenLoaded('claims', fn () => $this->claims->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
            ])->values()),
        ];
    }
}
