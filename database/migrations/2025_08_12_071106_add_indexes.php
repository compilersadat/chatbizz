<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('merchant_products', function (Blueprint $table) {
            $table->index(['merchant_id', 'product_id']);
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_products', function (Blueprint $table) {
            $table->dropIndex('merchant_products_merchant_id_product_id_index');
        });
    }
};
