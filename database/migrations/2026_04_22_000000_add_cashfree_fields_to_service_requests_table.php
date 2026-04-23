<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('payment_status');
            $table->string('payment_gateway_id')->nullable()->after('payment_gateway');
            $table->string('cashfree_order_id')->nullable()->after('razorpay_signature')->index();
            $table->text('cashfree_payment_session_id')->nullable()->after('cashfree_order_id');
            $table->string('cashfree_order_status')->nullable()->after('cashfree_payment_session_id');
            $table->timestamp('paid_at')->nullable()->after('payment_time');
            $table->string('delivery_payout_beneficiary_id')->nullable()->after('completion_otp');
            $table->string('delivery_payout_id')->nullable()->after('delivery_payout_beneficiary_id');
            $table->string('delivery_payout_status')->nullable()->after('delivery_payout_id');
            $table->timestamp('delivery_paid_at')->nullable()->after('delivery_payout_status');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex(['cashfree_order_id']);
            $table->dropColumn([
                'payment_gateway',
                'payment_gateway_id',
                'cashfree_order_id',
                'cashfree_payment_session_id',
                'cashfree_order_status',
                'paid_at',
                'delivery_payout_beneficiary_id',
                'delivery_payout_id',
                'delivery_payout_status',
                'delivery_paid_at',
            ]);
        });
    }
};
