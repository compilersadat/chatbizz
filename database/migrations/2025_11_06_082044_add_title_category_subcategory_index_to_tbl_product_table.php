<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // We need to add the index via raw SQL to specify a prefix length for the TEXT column.
        DB::statement(
            'ALTER TABLE tbl_product ADD INDEX tbl_product_cat_subcat_title_index (cat_id, subcat_id, title(191))'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE tbl_product DROP INDEX tbl_product_cat_subcat_title_index');
    }
};
