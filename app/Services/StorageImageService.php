<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class StorageImageService
{
    const string PLACEHOLDER_PATH = 'images/placeholder.png';

    public function getImage(?string $path, bool $replaceWithPlaceholderIfNotExists = true): ?string
    {
        $placeholder = $replaceWithPlaceholderIfNotExists ? asset(self::PLACEHOLDER_PATH) : null;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return $placeholder;
        }

        return asset("storage/$path");
    }
}
