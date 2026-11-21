<?php

namespace App\Jobs;

use App\Services\Images\Contracts\ImageVariantOwner;
use App\Services\Images\ImageVariantService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Removes an image's variants once no record uses the image any more. Seeded products
 * share image files, so a file replaced on one product may still be shown by others.
 */
class DeleteUnusedImageVariants implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $path,
    ) {}

    public function handle(ImageVariantService $images): void
    {
        if (! $images->isProcessable($this->path) || $this->isStillReferenced()) {
            return;
        }

        $images->deleteVariants($this->path);
    }

    private function isStillReferenced(): bool
    {
        foreach (config('images.models') as $modelClass) {
            $model = new $modelClass;
            if (! $model instanceof ImageVariantOwner || ! $model instanceof Model) {
                continue;
            }
            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true);

            foreach (array_keys($model->imageVariantColumns()) as $column) {
                $query = $model->newQuery();

                if ($usesSoftDeletes) {
                    $query->withoutGlobalScope(SoftDeletingScope::class);
                }

                if ($query->where($column, $this->path)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }
}
