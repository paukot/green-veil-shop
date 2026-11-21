<?php

namespace App\Jobs;

use App\Services\Images\ImageVariantService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessImageVariants implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 60];

    public function __construct(
        public readonly string $path,
        public readonly string $preset,
    ) {}

    /** One job per image at a time; seeded products share files. */
    public function uniqueId(): string
    {
        return "{$this->preset}:{$this->path}";
    }

    public function handle(ImageVariantService $images): void
    {
        $images->process($this->path, $this->preset);
    }
}
