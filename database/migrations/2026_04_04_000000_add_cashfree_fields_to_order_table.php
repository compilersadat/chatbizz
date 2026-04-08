<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('merchant_transaction_id');

            $table->string('cashfree_order_id')->nullable()->index()->after('payment_gateway');
            $table->text('cashfree_payment_session_id')->nullable()->after('cashfree_order_id');
            $table->string('cashfree_order_status')->nullable()->after('cashfree_payment_session_id');

            $table->string('payment_status')->default('pending')->after('status');
            $table->string('payment_gateway_id')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('payment_gateway_id');

            $table->decimal('merchant_amount', 12, 2)->default(0)->after('total_amount');
            $table->decimal('delivery_amount', 12, 2)->default(0)->after('merchant_amount');
            $table->decimal('admin_amount', 12, 2)->default(0)->after('delivery_amount');

            $table->string('merchant_payout_beneficiary_id')->nullable()->after('admin_amount');
            $table->string('merchant_payout_id')->nullable()->after('merchant_payout_beneficiary_id');
            $table->string('merchant_payout_status')->nullable()->after('merchant_payout_id');
            $table->timestamp('merchant_paid_at')->nullable()->after('merchant_payout_status');

            $table->string('delivery_payout_beneficiary_id')->nullable()->after('merchant_paid_at');
            $table->string('delivery_payout_id')->nullable()->after('delivery_payout_beneficiary_id');
            $table->string('delivery_payout_status')->nullable()->after('delivery_payout_id');
            $table->timestamp('delivery_paid_at')->nullable()->after('delivery_payout_status');
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
                'payment_status',
                'payment_gateway_id',
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