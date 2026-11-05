<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Skin Types & Pivot
        Schema::create('skin_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('product_skin_type', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('skin_type_id')->constrained('skin_types')->cascadeOnDelete();
            $table->primary(['product_id', 'skin_type_id']);
            $table->index(['skin_type_id', 'product_id']);
        });

        // 2. Hair Types & Pivot
        Schema::create('hair_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('product_hair_type', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('hair_type_id')->constrained('hair_types')->cascadeOnDelete();
            $table->primary(['product_id', 'hair_type_id']);
            $table->index(['hair_type_id', 'product_id']);
        });

        // 3. Effects / Actions & Pivot
        Schema::create('effects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('product_effect', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('effect_id')->constrained('effects')->cascadeOnDelete();
            $table->primary(['product_id', 'effect_id']);
            $table->index(['effect_id', 'product_id']);
        });

        // 4. Active & Botanical Ingredients & Pivot
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('inci_name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('product_ingredient', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->primary(['product_id', 'ingredient_id']);
            $table->index(['ingredient_id', 'product_id']);
        });

        // 5. Clean Beauty & Regulatory Claims & Pivot
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('product_claim', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
            $table->primary(['product_id', 'claim_id']);
            $table->index(['claim_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_claim');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('product_ingredient');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('product_effect');
        Schema::dropIfExists('effects');
        Schema::dropIfExists('product_hair_type');
        Schema::dropIfExists('hair_types');
        Schema::dropIfExists('product_skin_type');
        Schema::dropIfExists('skin_types');
    }
};
