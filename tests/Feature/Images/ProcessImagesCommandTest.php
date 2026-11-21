<?php

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    // Rows are created without their processing jobs, as the bulk seeders do.
    Queue::fake();
});

it('processes each stored image once even when several products share it', function () {
    Storage::disk('public')->putFileAs('products', UploadedFile::fake()->image('shared.jpg', 900, 900), 'shared.jpg');
    Storage::disk('public')->putFileAs('products', UploadedFile::fake()->image('own.jpg', 900, 900), 'own.jpg');
    Product::factory()->count(2)->create(['main_image' => 'products/shared.jpg']);
    Product::factory()->create(['main_image' => 'products/own.jpg']);

    $this->artisan('app:images:process', ['--preset' => 'product'])
        ->expectsOutputToContain('Processed 2, skipped 0')
        ->assertSuccessful();

    Storage::disk('public')->assertExists('products/variants/shared-800.webp');
    Storage::disk('public')->assertExists('products/variants/own-800.webp');
});

it('fails and names images that cannot be decoded', function () {
    Storage::disk('public')->put('products/broken.jpg', 'not an image');
    Product::factory()->create(['main_image' => 'products/broken.jpg']);

    $this->artisan('app:images:process', ['--preset' => 'product'])
        ->expectsOutputToContain('failed 1')
        ->assertFailed();
});
