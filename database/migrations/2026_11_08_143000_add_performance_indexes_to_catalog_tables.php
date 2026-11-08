<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'is_featured', 'sort_order', 'id'], 'products_active_featured_sort_idx');
            $table->index(['is_active', 'price'], 'products_active_price_idx');
            $table->index(['category_id', 'is_active', 'sort_order'], 'products_cat_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_active_featured_sort_idx');
            $table->dropIndex('products_active_price_idx');
            $table->dropIndex('products_cat_active_sort_idx');
        });
    }
};
