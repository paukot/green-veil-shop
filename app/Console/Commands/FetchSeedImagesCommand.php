<?php

namespace App\Console\Commands;

use Database\Seeders\Support\SeedImageCatalog;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchSeedImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-seed-images
                            {--force : Download again even when a file already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download the curated seed photography to the public disk';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $force = (bool) $this->option('force');
        $downloaded = 0;
        $skipped = 0;

        /** @var list<array{string, string}> $failures */
        $failures = [];

        $downloads = SeedImageCatalog::downloads();
        $progress = $this->output->createProgressBar(count($downloads));

        foreach ($downloads as ['path' => $path, 'url' => $url]) {
            $progress->advance();

            if (! $force && $disk->exists($path)) {
                $skipped++;

                continue;
            }

            try {
                $response = Http::timeout(20)->retry(2, 500)->get($url);
            } catch (ConnectionException|RequestException $exception) {
                $failures[] = [$path, $exception->getMessage()];

                continue;
            }

            if (! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                $failures[] = [$path, 'Response was not an image ('.($response->header('Content-Type') ?: 'no content type').').'];

                continue;
            }

            $disk->put($path, $response->body());
            $downloaded++;
        }

        $progress->finish();
        $this->newLine(2);

        $this->info("Downloaded {$downloaded}, skipped {$skipped} existing, failed ".count($failures).'.');

        if ($failures !== []) {
            $this->table(['File', 'Reason'], $failures);
            $this->error('Some seed images could not be downloaded. Replace their photo IDs in '.SeedImageCatalog::class.'.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
