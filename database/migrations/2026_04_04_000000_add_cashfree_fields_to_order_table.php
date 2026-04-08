<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable();
            $table->string('payment_status')->default('pending');

            $table->string('cashfree_order_id')->nullable()->index();
            $table->text('cashfree_payment_session_id')->nullable();
            $table->string('cashfree_order_status')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->decimal('merchant_amount', 12, 2)->default(0);
            $table->decimal('delivery_amount', 12, 2)->default(0);
            $table->decimal('admin_amount', 12, 2)->default(0);

            $table->string('merchant_payout_beneficiary_id')->nullable();
            $table->string('merchant_payout_id')->nullable();
            $table->string('merchant_payout_status')->nullable();
            $table->timestamp('merchant_paid_at')->nullable();

            $table->string('delivery_payout_beneficiary_id')->nullable();
            $table->string('delivery_payout_id')->nullable();
            $table->string('delivery_payout_status')->nullable();
            $table->timestamp('delivery_paid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway',
                'cashfree_order_id',
                'cashfree_payment_session_id',
                'cashfree_order_status',
                'paid_at',
                'merchant_amount',
                'delivery_amount',
                'admin_amount',
                'merchant_payout_beneficiary_id',
                'merchant_payout_id',
                'merchant_payout_status',
                'merchant_paid_at',
                'delivery_payout_beneficiary_id',
                'delivery_payout_id',
                'delivery_payout_status',
                'delivery_paid_at',
            ]);
        });
    }
};