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
        Schema::table('tbl_product', function (Blueprint $table) {
            $table->index(
                ['cat_id', 'subcat_id', 'title'],
                'tbl_product_cat_subcat_title_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_product', function (Blueprint $table) {
            $table->dropIndex('tbl_product_cat_subcat_title_index');
        });
    }
};
