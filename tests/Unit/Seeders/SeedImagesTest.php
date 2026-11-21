<?php

use Database\Seeders\Support\SeedImages;

test('gallery offsets never repeat the main image of the same product', function () {
    $pool = ['a.jpg', 'b.jpg', 'c.jpg', 'd.jpg', 'e.jpg'];

    foreach (['GV-SKI-00001', 'GV-MAK-00042', 'MAY-SKYHIGH'] as $sku) {
        $picks = [
            SeedImages::pick($pool, $sku),
            SeedImages::pick($pool, $sku, 1),
            SeedImages::pick($pool, $sku, 2),
        ];

        expect($picks)->toHaveCount(count(array_unique($picks)));
    }
});

test('picks nothing from an empty pool', function () {
    expect(SeedImages::pick([], 'GV-SKI-00001'))->toBeNull();
});
