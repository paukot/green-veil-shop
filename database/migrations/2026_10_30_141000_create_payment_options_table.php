<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_options', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // stripe, bank_transfer, cod
            $table->string('driver')->default('stripe'); // stripe, bank_transfer, cod
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable(); // bank account details or COD instructions
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_options');
    }
};
