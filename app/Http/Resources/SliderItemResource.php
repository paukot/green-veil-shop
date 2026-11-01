<?php

namespace App\Http\Resources;

use App\Models\SliderItem;
use App\Services\StorageImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SliderItem */
class SliderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'image' => (new StorageImageService)->getImage($this->image, false),
            'url' => $this->url,
            'url_opens_in_new_tab' => $this->url_opens_in_new_tab,
            'alt_text' => $this->alt_text,
            'button_label' => $this->button_label,
        ];
    }
}
