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
            $table->integer('stock')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->string('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
