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
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes('merchant_products');

        if (!array_key_exists('merchant_products_merchant_id_product_id_index', $indexes)) {
            Schema::table('merchant_products', function (Blueprint $table) {
                $table->index(['merchant_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes('merchant_products');

        if (array_key_exists('merchant_products_merchant_id_product_id_index', $indexes)) {
            Schema::table('merchant_products', function (Blueprint $table) {
                $table->dropIndex('merchant_products_merchant_id_product_id_index');
            });
        }
    }
};
