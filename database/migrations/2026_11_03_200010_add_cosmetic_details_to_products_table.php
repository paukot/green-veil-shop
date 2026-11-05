<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Brand relationship & series
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained('brands')->nullOnDelete();
            $table->string('series')->nullable()->after('brand_id')->index();
            $table->string('barcode', 32)->nullable()->after('sku')->index();

            // Volume & Unit Pricing (EU Directive 98/6/EC)
            $table->decimal('volume', 8, 2)->nullable()->after('compare_at_price');
            $table->string('unit', 16)->default('ml')->after('volume'); // ml, g, pcs, kg, l
            $table->unsignedSmallInteger('unit_price_base_amount')->default(100)->after('unit'); // e.g. 100 for per 100ml / 100g

            // Cosmetic Classifications & Targeting (Indexed for fast faceted filtering)
            $table->string('classification', 32)->nullable()->after('unit_price_base_amount')->index();
            $table->string('gender', 16)->default('unisex')->after('classification')->index();
            $table->string('age_range', 32)->nullable()->after('gender')->index();
            $table->string('formula_type', 32)->nullable()->after('age_range')->index();
            $table->string('spf', 16)->nullable()->after('formula_type')->index();
            $table->string('manufactured_in', 2)->nullable()->after('spf')->index();
            $table->unsignedSmallInteger('pao_months')->nullable()->after('manufactured_in')->index();

            // Display-Only Specifications (Flexible Key-Value)
            $table->json('custom_characteristics')->nullable()->after('pao_months');

            // Regulatory & Scientific Information
            $table->longText('inci_list')->nullable()->after('description');
            $table->text('how_to_use')->nullable()->after('inci_list');
            $table->text('warnings')->nullable()->after('how_to_use');
            $table->string('responsible_person')->nullable()->after('warnings');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn([
                'brand_id',
                'series',
                'barcode',
                'volume',
                'unit',
                'unit_price_base_amount',
                'classification',
                'gender',
                'age_range',
                'formula_type',
                'spf',
                'manufactured_in',
                'pao_months',
                'custom_characteristics',
                'inci_list',
                'how_to_use',
                'warnings',
                'responsible_person',
            ]);
        });
    }
};
