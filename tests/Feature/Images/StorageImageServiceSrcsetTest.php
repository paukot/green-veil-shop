<?php

use App\Services\StorageImageService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('lists every available variant width in the srcset', function () {
    Storage::disk('public')->put('products/variants/serum-400.webp', 'v');
    Storage::disk('public')->put('products/variants/serum-800.webp', 'v');

    $srcset = app(StorageImageService::class)->getSrcset('products/serum.jpg', 'product');

    expect($srcset)->toBe(
        asset('storage/products/variants/serum-400.webp').' 400w, '.asset('storage/products/variants/serum-800.webp').' 800w',
    );
});

it('has no srcset for images without variants, external URLs or vectors', function () {
    $images = app(StorageImageService::class);

    expect($images->getSrcset('products/unprocessed.jpg', 'product'))->toBeNull();
    expect($images->getSrcset('https://cdn.example.com/a.jpg', 'product'))->toBeNull();
    expect($images->getSrcset('brands/logo.svg', 'brand'))->toBeNull();
});
