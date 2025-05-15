<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('order_products', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained('tbl_order')->onDelete('cascade');
        $table->foreignId('product_id')->constrained('tbl_product')->onDelete('cascade');
        $table->integer('quantity');
        $table->decimal('price', 10, 2); // optional: individual product price
        $table->timestamps();
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
