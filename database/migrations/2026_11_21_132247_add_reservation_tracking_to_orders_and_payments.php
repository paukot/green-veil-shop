<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('total');
            $table->timestamp('reservations_released_at')->nullable()->after('paid_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('stripe_charge_id')->nullable()->index()->after('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['stripe_charge_id']);
            $table->dropColumn('stripe_charge_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['refunded_amount', 'reservations_released_at']);
        });
    }
};
