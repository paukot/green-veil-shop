<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Services\StorageImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'image' => (new StorageImageService)->getImage($this->image),

            'children' => SimpleCategoryResource::collection($this->whenLoaded('children')),
            'parent' => SimpleCategoryResource::make($this->whenLoaded('parent')),
            'products' => SimpleProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
