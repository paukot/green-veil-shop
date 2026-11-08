<?php

namespace App\Services\Cache;

use App\Enums\CacheKey;
use App\Enums\CacheTag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Throwable;

class CacheFlushChain
{
    /** @var list<string> */
    protected array $tags = [];

    /** @var list<string> */
    protected array $keys = [];

    /** @var list<string> */
    protected array $patterns = [];

    /**
     * Add tags to the flush chain.
     *
     * @param  list<CacheTag|string>|CacheTag|string  $tags
     */
    public function tags(array|CacheTag|string $tags): self
    {
        $tagList = is_array($tags) ? $tags : [$tags];

        foreach ($tagList as $tag) {
            $this->tags[] = $tag instanceof CacheTag ? $tag->value : (string) $tag;
        }

        $this->tags = array_values(array_unique($this->tags));

        return $this;
    }

    /**
     * Add specific keys to the flush chain.
     *
     * @param  list<CacheKey|string>|CacheKey|string  $keys
     */
    public function keys(array|CacheKey|string $keys): self
    {
        $keyList = is_array($keys) ? $keys : [$keys];

        foreach ($keyList as $key) {
            $this->keys[] = $key instanceof CacheKey ? $key->value : (string) $key;
        }

        $this->keys = array_values(array_unique($this->keys));

        return $this;
    }

    /**
     * Add Redis wildcard patterns to the flush chain (e.g. 'catalog.*').
     *
     * @param  list<string>|string  $patterns
     */
    public function patterns(array|string $patterns): self
    {
        $patternList = is_array($patterns) ? $patterns : [$patterns];

        foreach ($patternList as $pattern) {
            $this->patterns[] = (string) $pattern;
        }

        $this->patterns = array_values(array_unique($this->patterns));

        return $this;
    }

    /**
     * Execute the flush chain across tags, keys, and Redis patterns.
     */
    public function flush(): void
    {
        // 1. Flush tagged stores
        if (! empty($this->tags)) {
            try {
                Cache::tags($this->tags)->flush();
            } catch (Throwable) {
                // Ignore if tagging is unavailable
            }
        }

        // 2. Forget explicit keys
        foreach ($this->keys as $key) {
            AppCache::forget($key);
        }

        // 3. Scan & delete Redis patterns
        if (! empty($this->patterns)) {
            $this->flushRedisPatterns();
        }
    }

    /**
     * Flushes Redis patterns using non-blocking SCAN.
     */
    protected function flushRedisPatterns(): void
    {
        try {
            $connectionName = (string) config('cache.stores.redis.connection', 'cache');
            $redis = Redis::connection($connectionName);
            $prefix = (string) config('cache.prefix', '');

            foreach ($this->patterns as $pattern) {
                $search = $prefix.$pattern;
                $cursor = 0;

                do {
                    /** @var array<string>|false $matchedKeys */
                    $matchedKeys = $redis->scan($cursor, $search, 100);

                    if ($matchedKeys === false) {
                        break;
                    }

                    if (! empty($matchedKeys)) {
                        $redis->del(...$matchedKeys);
                    }
                } while ($cursor > 0);
            }
        } catch (Throwable) {
            // Redis unavailable or pattern execution failed gracefully
        }
    }
}
