<?php

use Database\Seeders\Support\SeedImageCatalog;

test('product photos are not reused across domains', function () {
    $photoIds = array_merge(...array_values(SeedImageCatalog::PRODUCTS));

    expect($photoIds)->toHaveCount(count(array_unique($photoIds)));
});

test('every product domain offers enough photos for a varied catalogue and gallery', function () {
    foreach (SeedImageCatalog::PRODUCTS as $domain => $photoIds) {
        expect(count($photoIds))->toBeGreaterThanOrEqual(5, "Domain {$domain} has too few photos.");
    }
});

test('every download has its own file', function () {
    $paths = array_column(SeedImageCatalog::downloads(), 'path');

    expect($paths)->toHaveCount(count(array_unique($paths)));
});
