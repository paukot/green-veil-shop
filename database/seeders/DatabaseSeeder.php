<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeders reference local copies of the curated photography; fetch any that are missing.
        // Offline, seeding still completes and the storefront falls back to its placeholder image.
        if (Artisan::call('app:fetch-seed-images') !== 0) {
            $this->command?->warn('Some seed images could not be downloaded; affected records will use the placeholder image.');
        }

        $this->call([
            AdminRoleSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            CosmeticLookupSeeder::class,
            ProductSeeder::class,
            CosmeticProductEnrichmentSeeder::class,
            ProductImageSeeder::class,
            DeliveryOptionSeeder::class,
            PaymentOptionSeeder::class,
            PromotionSeeder::class,
            SliderItemSeeder::class,
            OrderSeeder::class,
            OrderItemSeeder::class,
        ]);

        // Seeders insert rows in bulk (no model events), so build the web image variants once here.
        Artisan::call('app:images:process');
    }
}
