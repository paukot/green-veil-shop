<?php

use Database\Seeders\Support\SeedImageCatalog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Storage::fake('public');
    Sleep::fake();
});

it('downloads every catalogue image to the public disk', function () {
    Http::fake(['images.unsplash.com/*' => Http::response('jpeg-bytes', 200, ['Content-Type' => 'image/jpeg'])]);

    $this->artisan('app:fetch-seed-images')->assertSuccessful();

    foreach (SeedImageCatalog::downloads() as $download) {
        Storage::disk('public')->assertExists($download['path']);
    }
});

it('skips images that were already downloaded', function () {
    Http::fake(['images.unsplash.com/*' => Http::response('jpeg-bytes', 200, ['Content-Type' => 'image/jpeg'])]);
    $existingPath = SeedImageCatalog::downloads()[0]['path'];
    Storage::disk('public')->put($existingPath, 'already-here');

    $this->artisan('app:fetch-seed-images')->assertSuccessful();

    expect(Storage::disk('public')->get($existingPath))->toBe('already-here');
    Http::assertSentCount(count(SeedImageCatalog::downloads()) - 1);
});

it('downloads existing images again with --force', function () {
    Http::fake(['images.unsplash.com/*' => Http::response('fresh-bytes', 200, ['Content-Type' => 'image/jpeg'])]);
    $existingPath = SeedImageCatalog::downloads()[0]['path'];
    Storage::disk('public')->put($existingPath, 'stale');

    $this->artisan('app:fetch-seed-images', ['--force' => true])->assertSuccessful();

    expect(Storage::disk('public')->get($existingPath))->toBe('fresh-bytes');
});

it('fails and writes nothing for a photo that no longer exists', function () {
    Http::fake(['images.unsplash.com/*' => Http::response('Not Found', 404)]);

    $this->artisan('app:fetch-seed-images')->assertFailed();

    Storage::disk('public')->assertMissing(SeedImageCatalog::downloads()[0]['path']);
});

it('fails when the response is not an image', function () {
    Http::fake(['images.unsplash.com/*' => Http::response('<html>challenge</html>', 200, ['Content-Type' => 'text/html'])]);

    $this->artisan('app:fetch-seed-images')->assertFailed();

    Storage::disk('public')->assertMissing(SeedImageCatalog::downloads()[0]['path']);
});
