<?php

namespace App\Console\Commands;

use App\Services\Images\Contracts\ImageVariantOwner;
use App\Services\Images\ImageVariantService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Throwable;

class ProcessImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:images:process
                            {--preset= : Only process images that use this preset}
                            {--force : Rebuild variants even when they are up to date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compress and resize every stored image into its web variants';

    public function handle(ImageVariantService $images): int
    {
        $targets = $this->collectTargets($images);

        if ($targets->isEmpty()) {
            $this->info('No images to process.');

            return self::SUCCESS;
        }

        $processed = 0;
        $skipped = 0;

        /** @var list<array{string, string}> $failures */
        $failures = [];

        $this->withProgressBar($targets, function (array $target) use ($images, &$processed, &$skipped, &$failures): void {
            [$path, $preset] = $target;

            try {
                $images->process($path, $preset, force: (bool) $this->option('force'))
                    ? $processed++
                    : $skipped++;
            } catch (Throwable $exception) {
                $failures[] = [$path, $exception->getMessage()];
            }
        });

        $this->newLine(2);
        $this->info("Processed {$processed}, skipped {$skipped} up-to-date or non-raster, failed ".count($failures).'.');

        if ($failures !== []) {
            $this->table(['Image', 'Error'], $failures);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Distinct image paths with their preset, across every model that declares image columns.
     *
     * @return Collection<int, array{string, string}>
     */
    private function collectTargets(ImageVariantService $images): Collection
    {
        $onlyPreset = $this->option('preset');
        $targets = collect();

        foreach (config('images.models') as $modelClass) {
            $model = new $modelClass;
            if (! $model instanceof ImageVariantOwner || ! $model instanceof Model) {
                continue;
            }
            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true);

            foreach ($model->imageVariantColumns() as $column => $preset) {
                if ($onlyPreset && $onlyPreset !== $preset) {
                    continue;
                }

                $query = $model->newQuery();
                if ($usesSoftDeletes) {
                    $query->withoutGlobalScope(SoftDeletingScope::class);
                }

                $query->whereNotNull($column)
                    ->distinct()
                    ->pluck($column)
                    ->filter(fn (mixed $path): bool => is_string($path) && $images->isProcessable($path))
                    ->each(fn (string $path) => $targets->put("{$preset}:{$path}", [$path, $preset]));
            }
        }

        return $targets->values();
    }
}
