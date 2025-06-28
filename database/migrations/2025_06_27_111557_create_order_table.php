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
       
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('address_id');
                $table->string('contact_name');
                $table->string('contact_number');
                $table->decimal('sub_total', 10, 2);
                $table->decimal('delivery_charges', 10, 2);
                $table->decimal('platform_fee', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2);
                $table->string('merchant_transaction_id')->unique();
                $table->enum('status', [
                    'pending',       // Placed, awaiting payment or confirmation
                    'confirmed',     // Payment successful, order confirmed by shop
                    'packed',        // Packed, ready for delivery
                    'paid',          // Payment successful (optional if 'confirmed' is used)
                    'rejected',      // Rejected by shop (NEW)
                    'failed',        // Payment failed
                    'canceled',      // Canceled by user/shop
                    'refunded',      // Payment refunded
                ])->default('pending');
                
                $table->enum('delivery_status', [
                    'pending',         // Not yet assigned to delivery
                    'assigned',        // Assigned to delivery partner
                    'packed',          // Packed, ready for pickup
                    'picked_up',       // Picked up by delivery
                    'in_transit',      // On the way
                    'delivered',       // Delivered to user
                    'cancelled',       // Delivery cancelled
                ])->default('pending');
                
                $table->unsignedBigInteger('delivery_partner_id')->nullable();
                $table->unsignedBigInteger('shop_id');
                $table->string('razorpay_order_id')->nullable();
                $table->string('payment_gateway_id')->nullable();

                $table->timestamps();
            });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
