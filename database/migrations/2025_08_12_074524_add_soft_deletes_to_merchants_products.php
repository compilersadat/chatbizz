<?php

// database/migrations/2025_08_12_000001_add_soft_deletes_to_merchant_products.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('merchant_products', 'deleted_at')) {
            Schema::table('merchant_products', function (Blueprint $table) {
                $table->softDeletes(); // adds deleted_at
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('merchant_products', 'deleted_at')) {
            Schema::table('merchant_products', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};

