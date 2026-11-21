<?php

use App\Jobs\ProcessImageVariants;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('queues variant processing when a product gets an image', function () {
    Queue::fake();

    Product::factory()->create(['main_image' => 'products/new.jpg']);

    Queue::assertPushed(
        ProcessImageVariants::class,
        fn (ProcessImageVariants $job): bool => $job->path === 'products/new.jpg' && $job->preset === 'product',
    );
});

it('keeps a replaced image\'s variants while another product still uses it', function () {
    Storage::fake('public');
    Storage::disk('public')->put('products/variants/shared-400.webp', 'variant');
    $first = Product::factory()->create(['main_image' => 'products/shared.jpg']);
    $second = Product::factory()->create(['main_image' => 'products/shared.jpg']);

    $first->update(['main_image' => 'products/replacement.jpg']);

    Storage::disk('public')->assertExists('products/variants/shared-400.webp');

    $second->update(['main_image' => 'products/replacement.jpg']);

    Storage::disk('public')->assertMissing('products/variants/shared-400.webp');
});
