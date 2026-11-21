<?php

namespace Database\Seeders\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Hands seeders local image paths that exist on the public disk.
 *
 * Pools only contain downloaded files (see app:fetch-seed-images). When nothing has been
 * downloaded, picks return null and the storefront shows its placeholder rather than a
 * broken image.
 */
final class SeedImages
{
    /** @var array<string, list<string>> */
    private array $productPools = [];

    public function __construct(
        private readonly Filesystem $disk,
    ) {}

    public static function make(): self
    {
        return new self(Storage::disk('public'));
    }

    /**
     * Downloaded product photos for a catalogue domain, falling back to skincare.
     *
     * @return list<string>
     */
    public function productPool(string $domain): array
    {
        if (! array_key_exists($domain, SeedImageCatalog::PRODUCTS)) {
            $domain = 'skincare';
        }

        return $this->productPools[$domain] ??= array_values(array_filter(
            array_map(
                fn (int $index): string => SeedImageCatalog::productPath($domain, $index),
                array_keys(SeedImageCatalog::PRODUCTS[$domain]),
            ),
            fn (string $path): bool => $this->disk->exists($path),
        ));
    }

    /**
     * A product photo for the domain, chosen deterministically from the seed value.
     *
     * Consecutive offsets give distinct photos while the pool is large enough, so a
     * product's gallery (offsets 1 and 2) never repeats its main image (offset 0).
     */
    public function productImage(string $domain, string|int $seed, int $offset = 0): ?string
    {
        return self::pick($this->productPool($domain), $seed, $offset);
    }

    public function slideImage(string $slide): ?string
    {
        $path = SeedImageCatalog::slidePath($slide);

        return $this->disk->exists($path) ? $path : null;
    }

    /**
     * @param  list<string>  $pool
     */
    public static function pick(array $pool, string|int $seed, int $offset = 0): ?string
    {
        if ($pool === []) {
            return null;
        }

        return $pool[(crc32((string) $seed) + $offset) % count($pool)];
    }

    /**
     * Write a wordmark logo for a brand and return its public-disk path.
     */
    public function brandLogo(string $brandName): string
    {
        $path = 'brands/'.Str::slug($brandName).'.svg';
        $hue = crc32($brandName) % 360;
        $name = htmlspecialchars($brandName, ENT_XML1 | ENT_QUOTES);
        $fontSize = mb_strlen($brandName) > 16 ? 22 : 28;

        $this->disk->put($path, <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="320" height="160" viewBox="0 0 320 160">
              <rect width="320" height="160" rx="24" fill="hsl({$hue}, 32%, 94%)"/>
              <text x="160" y="80" fill="hsl({$hue}, 28%, 28%)" font-family="Georgia, 'Times New Roman', serif" font-size="{$fontSize}" text-anchor="middle" dominant-baseline="central">{$name}</text>
            </svg>
            SVG);

        return $path;
    }

    /**
     * Write a shade swatch for a colour variant and return its public-disk path.
     */
    public function shadeSwatch(string $shadeName, string $hex): string
    {
        $path = 'product-variants/swatch-'.Str::slug($shadeName).'.svg';

        if (! $this->disk->exists($path)) {
            $colour = htmlspecialchars($hex, ENT_XML1 | ENT_QUOTES);

            $this->disk->put($path, <<<SVG
                <svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400">
                  <rect width="400" height="400" fill="#f6f3eb"/>
                  <circle cx="200" cy="200" r="150" fill="{$colour}"/>
                  <ellipse cx="150" cy="140" rx="60" ry="28" fill="#ffffff" fill-opacity="0.18" transform="rotate(-30 150 140)"/>
                </svg>
                SVG);
        }

        return $path;
    }
}
