<?php

namespace App\Services\Images\Contracts;

/**
 * A model whose image columns get compressed, resized web variants.
 */
interface ImageVariantOwner
{
    /**
     * @return array<string, string> image column => preset name from config('images.presets')
     */
    public function imageVariantColumns(): array;
}
