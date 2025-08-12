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
        Schema::table('merchants', fn (Blueprint $t) => $t->index('name'));
        Schema::table('products', fn (Blueprint $t) => $t->index('name'));
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
