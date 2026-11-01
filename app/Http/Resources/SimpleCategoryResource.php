<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Services\StorageImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class SimpleCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'image' => (new StorageImageService)->getImage($this->image),
            'name' => $this->name,
            'description' => $this->description,

            'products_count' => $this->products_count,
        ];
    }
}
