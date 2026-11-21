<?php

use App\Services\Images\ImageVariantService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Width and height of a stored image.
 *
 * @return array{0: int, 1: int}
 */
function storedImageSize(string $path): array
{
    [$width, $height] = getimagesize(Storage::disk('public')->path($path));

    return [$width, $height];
}

it('caps an oversized master and writes a webp variant for every preset width', function () {
    Storage::disk('public')->putFileAs('products', UploadedFile::fake()->image('big.jpg', 3000, 2000), 'big.jpg');

    $processed = app(ImageVariantService::class)->process('products/big.jpg', 'product');

    expect($processed)->toBeTrue();
    expect(storedImageSize('products/big.jpg'))->toBe([2000, 1333]);
    expect(storedImageSize('products/variants/big-400.webp')[0])->toBe(400);
    expect(storedImageSize('products/variants/big-800.webp')[0])->toBe(800);
    expect(storedImageSize('products/variants/big-1200.webp')[0])->toBe(1200);
});

it('never upscales a narrow image and keeps only a compressed copy at its own size', function () {
    Storage::disk('public')->putFileAs('products', UploadedFile::fake()->image('small.jpg', 300, 200), 'small.jpg');

    app(ImageVariantService::class)->process('products/small.jpg', 'product');

    expect(storedImageSize('products/variants/small-400.webp')[0])->toBe(300);
    Storage::disk('public')->assertMissing('products/variants/small-800.webp');
    expect(storedImageSize('products/small.jpg'))->toBe([300, 200]);
});

it('leaves vector images untouched', function () {
    Storage::disk('public')->put('brands/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

    $processed = app(ImageVariantService::class)->process('brands/logo.svg', 'brand');

    expect($processed)->toBeFalse();
    Storage::disk('public')->assertMissing('brands/variants/logo-200.webp');
});

it('skips images whose variants are already up to date', function () {
    Storage::disk('public')->putFileAs('products', UploadedFile::fake()->image('again.jpg', 900, 900), 'again.jpg');
    $images = app(ImageVariantService::class);
    $images->process('products/again.jpg', 'product');

    expect($images->process('products/again.jpg', 'product'))->toBeFalse();
    expect($images->process('products/again.jpg', 'product', force: true))->toBeTrue();
});
