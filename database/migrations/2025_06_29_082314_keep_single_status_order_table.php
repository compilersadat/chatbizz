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
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_status')) {
                $table->dropColumn('delivery_status');
            }
        });

        // Modify the status column enum
        // Note: Laravel can't modify enums easily; use a raw statement
        // Adjust the type if your status column isn't enum
        \DB::statement("ALTER TABLE `orders` CHANGE `status` `status` ENUM(
            'pending',
            'paid',
            'assigned',
            'confirmed',
            'picked_up',
            'in_transit',
            'delivered',
            'rejected',
            'failed',
            'canceled',
            'refunded'
        ) NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
