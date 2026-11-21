<?php

namespace App\Services;

use App\Services\Images\ImageVariantService;
use Illuminate\Support\Facades\Storage;

class StorageImageService
{
    const string PLACEHOLDER_PATH = 'images/placeholder.png';

    public function getImage(?string $path, bool $replaceWithPlaceholderIfNotExists = true): ?string
    {
        if (filled($path) && (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))) {
            return $path;
        }

        $placeholder = $replaceWithPlaceholderIfNotExists ? asset(self::PLACEHOLDER_PATH) : null;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return $placeholder;
        }

        return asset("storage/$path");
    }

    /**
     * The `srcset` for an image's compressed web variants, or null when it has none
     * (external URL, SVG, missing file, or not processed yet). Pair it with the URL from
     * getImage(), which stays the fallback `src`.
     */
    public function getSrcset(?string $path, string $preset): ?string
    {
        $images = app(ImageVariantService::class);

        if ($path === null || ! $images->isProcessable($path)) {
            return null;
        }

        $widths = $images->availableWidths($path, $preset);

        if ($widths === []) {
            return null;
        }

        return collect($widths)
            ->map(fn (int $width): string => asset('storage/'.$images->variantPath($path, $width))." {$width}w")
            ->implode(', ');
    }
}
