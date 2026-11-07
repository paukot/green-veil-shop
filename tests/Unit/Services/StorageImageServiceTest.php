<?php

use App\Services\StorageImageService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

describe('StorageImageService', function () {
    it('returns remote http and https urls as is', function () {
        $service = new StorageImageService;

        $httpsUrl = 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=600&q=80';
        $httpUrl = 'http://example.com/images/perfume.jpg';

        expect($service->getImage($httpsUrl))->toBe($httpsUrl)
            ->and($service->getImage($httpUrl))->toBe($httpUrl);
    });

    it('returns placeholder when local path does not exist', function () {
        Storage::fake('public');
        $service = new StorageImageService;

        $result = $service->getImage('products/nonexistent.jpg');
        expect((string) $result)->toContain('images/placeholder.png');

        $nullResult = $service->getImage('products/nonexistent.jpg', false);
        expect($nullResult)->toBeNull();
    });

    it('returns storage asset url when file exists', function () {
        Storage::fake('public');
        Storage::disk('public')->put('products/serum.jpg', 'fake-image-content');

        $service = new StorageImageService;
        $result = $service->getImage('products/serum.jpg');

        expect((string) $result)->toContain('storage/products/serum.jpg');
    });
});
