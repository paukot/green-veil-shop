<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('public_id', 26)->nullable()->unique()->after('id');
            $table->foreignId('payment_option_id')->nullable()->after('delivery_option_id')->constrained()->nullOnDelete();
            $table->string('stripe_session_id')->nullable()->index()->after('stripe_payment_intent_id');
            $table->timestamp('placed_at')->nullable()->after('notes');
            $table->timestamp('paid_at')->nullable()->after('placed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_option_id']);
            $table->dropColumn([
                'public_id',
                'payment_option_id',
                'stripe_session_id',
                'placed_at',
                'paid_at',
            ]);
        });
    }
};
