<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Merchants.name ---
        if (Schema::hasColumn('merchants', 'name')) {
            $col = DB::table('information_schema.COLUMNS')
                ->select('DATA_TYPE', 'CHARACTER_MAXIMUM_LENGTH')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'merchants')
                ->where('COLUMN_NAME', 'name')
                ->first();

            // Change to VARCHAR(191) only if not already varchar(191)
            if (!$col || strtolower($col->DATA_TYPE) !== 'varchar' || (int) $col->CHARACTER_MAXIMUM_LENGTH !== 191) {
                DB::statement('ALTER TABLE `merchants` MODIFY `name` VARCHAR(191)');
            }

            // Add index on name if not exists
            $hasIndex = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'merchants')
                ->where('COLUMN_NAME', 'name')
                ->exists();

            if (!$hasIndex) {
                DB::statement('CREATE INDEX `merchants_name_index` ON `merchants` (`name`)');
            }
        }

        // --- Order_products.name ---
        if (Schema::hasColumn('order_products', 'name')) {
            $col = DB::table('information_schema.COLUMNS')
                ->select('DATA_TYPE', 'CHARACTER_MAXIMUM_LENGTH')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'order_products')
                ->where('COLUMN_NAME', 'name')
                ->first();

            if (!$col || strtolower($col->DATA_TYPE) !== 'varchar' || (int) $col->CHARACTER_MAXIMUM_LENGTH !== 191) {
                DB::statement('ALTER TABLE `order_products` MODIFY `name` VARCHAR(191)');
            }

            $hasIndex = DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'order_products')
                ->where('COLUMN_NAME', 'name')
                ->exists();

            if (!$hasIndex) {
                DB::statement('CREATE INDEX `order_products_name_index` ON `order_products` (`name`)');
            }
        }
    }

    public function down(): void
    {
        // Drop indexes only if they exist

        $merchantsIndexExists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'merchants')
            ->where('INDEX_NAME', 'merchants_name_index')
            ->exists();

        if ($merchantsIndexExists) {
            DB::statement('DROP INDEX `merchants_name_index` ON `merchants`');
        }

        $orderProductsIndexExists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'order_products')
            ->where('INDEX_NAME', 'order_products_name_index')
            ->exists();

        if ($orderProductsIndexExists) {
            DB::statement('DROP INDEX `order_products_name_index` ON `order_products`');
        }

        // Optional: revert column types back to TEXT (skip if you don't want this)
        if (Schema::hasColumn('merchants', 'name')) {
            DB::statement('ALTER TABLE `merchants` MODIFY `name` TEXT');
        }
        if (Schema::hasColumn('order_products', 'name')) {
            DB::statement('ALTER TABLE `order_products` MODIFY `name` TEXT');
        }
    }
};
