<?php

namespace App\Services\Cache;

use App\Enums\CacheKey;
use App\Enums\CacheTag;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CacheFlushChain
{
    /** @var list<string> */
    protected array $tags = [];

    /** @var list<string> */
    protected array $keys = [];

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
     * Execute the flush chain across tags and keys.
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
    }
}
