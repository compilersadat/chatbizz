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
            $table->boolean('merchant_payment_settled')
                ->default(false)
                ->after('payment_gateway_id');
            $table->boolean('rider_payment_settled')
                ->default(false)
                ->after('merchant_payment_settled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['merchant_payment_settled', 'rider_payment_settled']);
        });
    }
};
