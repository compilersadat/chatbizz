<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();

        // Merchants.name
        if (Schema::hasColumn('merchants', 'name')) {
            $column = $sm->listTableColumns('merchants')['name'];
            if (strtolower($column->getType()->getName()) !== 'string' || $column->getLength() !== 191) {
                Schema::table('merchants', function (Blueprint $table) {
                    $table->string('name', 191)->change();
                });
            }

            $indexes = $sm->listTableIndexes('merchants');
            if (!array_key_exists('merchants_name_index', $indexes)) {
                Schema::table('merchants', function (Blueprint $table) {
                    $table->index('name');
                });
            }
        }

        // Order_products.name
        if (Schema::hasColumn('order_products', 'name')) {
            $column = $sm->listTableColumns('order_products')['name'];
            if (strtolower($column->getType()->getName()) !== 'string' || $column->getLength() !== 191) {
                Schema::table('order_products', function (Blueprint $table) {
                    $table->string('name', 191)->change();
                });
            }

            $indexes = $sm->listTableIndexes('order_products');
            if (!array_key_exists('order_products_name_index', $indexes)) {
                Schema::table('order_products', function (Blueprint $table) {
                    $table->index('name');
                });
            }
        }
    }

    public function down(): void
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();

        // Merchants.name
        $indexes = $sm->listTableIndexes('merchants');
        if (array_key_exists('merchants_name_index', $indexes)) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->dropIndex('merchants_name_index');
            });
        }
        // Optional: revert to TEXT
        Schema::table('merchants', function (Blueprint $table) {
            $table->text('name')->change();
        });

        // Order_products.name
        $indexes = $sm->listTableIndexes('order_products');
        if (array_key_exists('order_products_name_index', $indexes)) {
            Schema::table('order_products', function (Blueprint $table) {
                $table->dropIndex('order_products_name_index');
            });
        }
        // Optional: revert to TEXT
        Schema::table('order_products', function (Blueprint $table) {
            $table->text('name')->change();
        });
    }
};
