<?php

namespace App\Http\Resources;

use App\Models\ProductImage;
use App\Services\StorageImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductImage */
class ProductImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'path' => (new StorageImageService)->getImage($this->path),
            'srcset' => (new StorageImageService)->getSrcset($this->path, 'product'),
            'alt_text' => $this->alt_text,
            'is_primary' => $this->is_primary,
        ];
    }
}
