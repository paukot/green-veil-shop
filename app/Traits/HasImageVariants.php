<?php

namespace App\Traits;

use App\Jobs\DeleteUnusedImageVariants;
use App\Jobs\ProcessImageVariants;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Generates compressed, resized variants whenever a declared image column changes.
 *
 * A model opts in by implementing ImageVariantOwner, using this trait and declaring
 * its image columns with their presets:
 *
 *     protected array $imageVariants = ['main_image' => 'product'];
 *
 * List the model in config('images.models') too, so the backfill command and the
 * shared-file check know about it.
 */
trait HasImageVariants
{
    public static function bootHasImageVariants(): void
    {
        static::saved(function (self $model): void {
            foreach ($model->imageVariantColumns() as $column => $preset) {
                if (! $model->wasRecentlyCreated && ! $model->wasChanged($column)) {
                    continue;
                }

                $newPath = $model->getAttribute($column);
                if (is_string($newPath) && $newPath !== '') {
                    ProcessImageVariants::dispatch($newPath, $preset);
                }

                $previousPath = $model->getPrevious()[$column] ?? null;
                if (is_string($previousPath) && $previousPath !== '' && $previousPath !== $newPath) {
                    DeleteUnusedImageVariants::dispatch($previousPath);
                }
            }
        });

        // A soft-deleted row can be restored, so its images are only cleaned up when it is
        // removed for good.
        $removalEvent = in_array(SoftDeletes::class, class_uses_recursive(static::class), true)
            ? 'forceDeleted'
            : 'deleted';

        static::registerModelEvent($removalEvent, function (self $model): void {
            foreach ($model->imageVariantColumns() as $column => $preset) {
                $path = $model->getAttribute($column);
                if (is_string($path) && $path !== '') {
                    DeleteUnusedImageVariants::dispatch($path);
                }
            }
        });
    }

    /**
     * @return array<string, string> image column => preset
     */
    public function imageVariantColumns(): array
    {
        return $this->imageVariants;
    }
}
