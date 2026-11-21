<?php

namespace App\Services\Images;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Turns an uploaded image into a capped master plus compressed WebP variants.
 *
 * Variants are named after the original (products/abc.jpg -> products/variants/abc-400.webp),
 * so nothing extra is stored in the database and URLs can be derived from the path alone.
 */
class ImageVariantService
{
    private ?ImageManager $manager = null;

    /**
     * Process an image for a preset. Returns false when there was nothing to do
     * (not a raster image, missing file, or variants already up to date).
     */
    public function process(string $path, string $preset, bool $force = false): bool
    {
        $disk = $this->disk();

        if (! $this->isProcessable($path) || ! $disk->exists($path)) {
            return false;
        }

        if (! $force && $this->isUpToDate($path, $preset)) {
            return false;
        }

        $image = $this->manager()->read($disk->get($path));

        $this->writeMaster($path, $image);

        $widths = $this->widths($preset);

        foreach ($widths as $width) {
            // Never upscale. The smallest width is always written (at native size when the
            // image is narrower), so every processed image has at least one compressed copy.
            if ($width > $image->width() && $width !== $widths[0]) {
                continue;
            }

            $variant = (clone $image)->scaleDown(width: $width);
            $disk->put(
                $this->variantPath($path, $width),
                (string) $variant->toWebp(quality: (int) config('images.variant_quality')),
            );
        }

        return true;
    }

    /**
     * Variant widths that exist on disk for the path, smallest first.
     *
     * @return list<int>
     */
    public function availableWidths(string $path, string $preset): array
    {
        if (! $this->isProcessable($path)) {
            return [];
        }

        $disk = $this->disk();

        return array_values(array_filter(
            $this->widths($preset),
            fn (int $width): bool => $disk->exists($this->variantPath($path, $width)),
        ));
    }

    public function variantPath(string $path, int $width): string
    {
        $info = pathinfo($path);
        $directory = ($info['dirname'] ?? '.') === '.' ? '' : $info['dirname'].'/';

        return "{$directory}variants/{$info['filename']}-{$width}.webp";
    }

    /**
     * Remove every variant of an image; the original is left alone.
     */
    public function deleteVariants(string $path): void
    {
        $variantPaths = [];

        foreach (config()->array('images.presets') as $widths) {
            foreach ((array) $widths as $width) {
                $variantPaths[] = $this->variantPath($path, (int) $width);
            }
        }

        $this->disk()->delete(array_values(array_unique($variantPaths)));
    }

    public function isProcessable(?string $path): bool
    {
        if (blank($path) || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }

        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), config('images.extensions'), true);
    }

    /**
     * @return list<int>
     */
    public function widths(string $preset): array
    {
        $widths = config("images.presets.{$preset}");

        if (! is_array($widths)) {
            throw new \InvalidArgumentException("Unknown image preset [{$preset}].");
        }

        sort($widths);

        return $widths;
    }

    /**
     * Cap the stored original so oversized uploads do not waste disk or bandwidth.
     * Images already within the limit keep their original bytes.
     */
    private function writeMaster(string $path, ImageInterface $image): void
    {
        $max = (int) config('images.master_max');

        if ($image->width() <= $max && $image->height() <= $max) {
            return;
        }

        $image->width() >= $image->height()
            ? $image->scaleDown(width: $max)
            : $image->scaleDown(height: $max);

        $this->disk()->put(
            $path,
            (string) $image->encodeByExtension(
                pathinfo($path, PATHINFO_EXTENSION),
                quality: (int) config('images.master_quality'),
            ),
        );
    }

    /**
     * Variants count as current when the smallest one (always generated) exists and is
     * newer than the original.
     */
    private function isUpToDate(string $path, string $preset): bool
    {
        $disk = $this->disk();
        $smallest = $this->variantPath($path, $this->widths($preset)[0]);

        return $disk->exists($smallest) && $disk->lastModified($smallest) >= $disk->lastModified($path);
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('images.disk'));
    }

    private function manager(): ImageManager
    {
        return $this->manager ??= ImageManager::gd(
            autoOrientation: true,
            decodeAnimation: false,
            strip: true,
        );
    }
}
