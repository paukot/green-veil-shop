<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_shipping_available')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();
        $countries = [
            // Pre-flagged as shipping destinations (matching existing Checkout defaults)
            ['code' => 'IE', 'name' => 'Ireland', 'is_active' => true, 'is_shipping_available' => true, 'sort_order' => 1],
            ['code' => 'GB', 'name' => 'United Kingdom', 'is_active' => true, 'is_shipping_available' => true, 'sort_order' => 2],
            ['code' => 'FR', 'name' => 'France', 'is_active' => true, 'is_shipping_available' => true, 'sort_order' => 3],
            ['code' => 'DE', 'name' => 'Germany', 'is_active' => true, 'is_shipping_available' => true, 'sort_order' => 4],
            ['code' => 'ES', 'name' => 'Spain', 'is_active' => true, 'is_shipping_available' => true, 'sort_order' => 5],
            ['code' => 'IT', 'name' => 'Italy', 'is_active' => true, 'is_shipping_available' => true, 'sort_order' => 6],
            ['code' => 'NL', 'name' => 'Netherlands', 'is_active' => true, 'is_shipping_available' => true, 'sort_order' => 7],

            // Cosmetic manufacturing & brand origin countries
            ['code' => 'PL', 'name' => 'Poland', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 10],
            ['code' => 'US', 'name' => 'United States', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 11],
            ['code' => 'KR', 'name' => 'South Korea', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 12],
            ['code' => 'JP', 'name' => 'Japan', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 13],
            ['code' => 'CH', 'name' => 'Switzerland', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 14],
            ['code' => 'CA', 'name' => 'Canada', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 15],
            ['code' => 'SE', 'name' => 'Sweden', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 16],
            ['code' => 'AU', 'name' => 'Australia', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 17],
            ['code' => 'BE', 'name' => 'Belgium', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 18],
            ['code' => 'DK', 'name' => 'Denmark', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 19],
            ['code' => 'FI', 'name' => 'Finland', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 20],
            ['code' => 'NO', 'name' => 'Norway', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 21],
            ['code' => 'AT', 'name' => 'Austria', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 22],
            ['code' => 'IL', 'name' => 'Israel', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 23],
            ['code' => 'GR', 'name' => 'Greece', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 24],
            ['code' => 'PT', 'name' => 'Portugal', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 25],
            ['code' => 'BR', 'name' => 'Brazil', 'is_active' => true, 'is_shipping_available' => false, 'sort_order' => 26],
        ];

        DB::table('countries')->insert(
            array_map(fn (array $country) => array_merge($country, [
                'created_at' => $now,
                'updated_at' => $now,
            ]), $countries)
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
