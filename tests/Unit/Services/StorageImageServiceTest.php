<?php

namespace Tests\Unit\Services;

use App\Services\StorageImageService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageImageServiceTest extends TestCase
{
    public function test_it_returns_remote_http_and_https_urls_as_is(): void
    {
        $service = new StorageImageService;

        $httpsUrl = 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=600&q=80';
        $httpUrl = 'http://example.com/images/perfume.jpg';

        $this->assertSame($httpsUrl, $service->getImage($httpsUrl));
        $this->assertSame($httpUrl, $service->getImage($httpUrl));
    }

    public function test_it_returns_placeholder_when_local_path_does_not_exist(): void
    {
        Storage::fake('public');
        $service = new StorageImageService;

        $result = $service->getImage('products/nonexistent.jpg');
        $this->assertStringContainsString('images/placeholder.png', (string) $result);

        $nullResult = $service->getImage('products/nonexistent.jpg', false);
        $this->assertNull($nullResult);
    }

    public function test_it_returns_storage_asset_url_when_file_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/serum.jpg', 'fake-image-content');

        $service = new StorageImageService;
        $result = $service->getImage('products/serum.jpg');

        $this->assertStringContainsString('storage/products/serum.jpg', (string) $result);
    }
}
