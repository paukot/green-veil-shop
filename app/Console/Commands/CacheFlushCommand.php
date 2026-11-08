<?php

namespace App\Console\Commands;

use App\Enums\CacheTag;
use App\Services\Cache\AppCache;
use Illuminate\Console\Command;

class CacheFlushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-flush
                            {--tag= : Domain tag to flush (catalog, categories, home, shipping, settings, promotions, taxonomies)}
                            {--key= : Explicit cache key to forget}
                            {--all : Flush all managed application caches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush managed application cache by tag, key, or entirely';

    public function handle(): int
    {
        $all = (bool) $this->option('all');
        $tag = $this->option('tag');
        $key = $this->option('key');

        if (! $all && ! $tag && ! $key) {
            $all = true;
        }

        if ($all) {
            $this->info('Flushing all application caches...');
            AppCache::flushAll();
            $this->components->info('All application caches flushed successfully.');

            return self::SUCCESS;
        }

        if (is_string($key) && $key !== '') {
            $this->info("Forgetting cache key [{$key}]...");
            AppCache::forget($key);
            $this->components->info("Cache key [{$key}] removed.");
        }

        if (is_string($tag) && $tag !== '') {
            $matchedTag = $this->resolveCacheTag($tag);
            if ($matchedTag === null) {
                $this->error("Unknown cache tag [{$tag}]. Valid tags: ".implode(', ', array_map(fn (CacheTag $t) => strtolower($t->name), CacheTag::cases())));

                return self::FAILURE;
            }

            $this->info("Flushing cache tag [{$matchedTag->value}]...");
            AppCache::flushTag($matchedTag);
            $this->components->info("Cache tag [{$matchedTag->name}] flushed successfully.");
        }

        return self::SUCCESS;
    }

    protected function resolveCacheTag(string $input): ?CacheTag
    {
        $normalized = strtolower(trim($input));

        foreach (CacheTag::cases() as $tag) {
            if (strtolower($tag->name) === $normalized || strtolower($tag->value) === $normalized || 'tag:'.$normalized === strtolower($tag->value)) {
                return $tag;
            }
        }

        return null;
    }
}
