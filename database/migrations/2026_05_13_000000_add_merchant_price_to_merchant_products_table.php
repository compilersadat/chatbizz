<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('merchant_products', 'merchant_price')) {
            Schema::table('merchant_products', function (Blueprint $table) {
                $table->decimal('merchant_price', 10, 2)->nullable()->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('merchant_products', 'merchant_price')) {
            Schema::table('merchant_products', function (Blueprint $table) {
                $table->dropColumn('merchant_price');
            });
        }
    }
};
