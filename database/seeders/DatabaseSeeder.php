<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
    }
}
