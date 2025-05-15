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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->string('mobile');
            $table->text('address');
            $table->text('lat')->nullable();
            $table->text('lang')->nullable();
            $table->text('thumbnail');
            $table->integer('status');
            $table->foreignId('catagory_id')->constrained('merchant_catagory')->onDelete('cascade'); // Foreign key to tbl_pcat
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
