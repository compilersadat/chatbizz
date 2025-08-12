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
        // migration to alter column
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('name', 191)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('name', 191)->change();
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->index('name');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->index('name');
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
