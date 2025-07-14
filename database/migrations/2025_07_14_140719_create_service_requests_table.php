<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('pickup_address');
            $table->string('pickup_lat', 30);
            $table->string('pickup_long', 30);
            $table->string('contact_name')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('note')->nullable();

            $table->enum('status', [
                'pending',      // New, awaiting action
                'accepted',     // Accepted by delivery person
                'inprocess',    // Delivery in process
                'cancelled',    // Cancelled by user or system
                'completed'     // Delivery completed
            ])->default('pending');

            // Payment fields
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamp('payment_time')->nullable();
            $table->unsignedBigInteger('delivery_partner_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('merchants')->onDelete('cascade');
        });
    }
    public function down()
    {
        Schema::dropIfExists('service_requests');
    }
}


