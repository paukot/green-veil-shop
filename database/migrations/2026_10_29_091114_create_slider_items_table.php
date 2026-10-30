<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slider_items', function (Blueprint $table) {
            $table->id();
            $table->string('image');
            $table->string('url')->nullable();          // target URL when clicked
            $table->boolean('url_opens_in_new_tab')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable(); // scheduled display window
            $table->timestamp('ends_at')->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('button_label')->nullable();   // CTA button text
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slider_items');
    }
};
