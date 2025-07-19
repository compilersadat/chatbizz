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
        Schema::create('merchant_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index(); // Merchant reference

            // Bank details
            $table->string('account_holder_name');
            $table->string('bank_account_number');
            $table->string('ifsc_code');
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();

            // RazorpayX integration
            $table->string('razorpay_contact_id')->nullable();
            $table->string('razorpay_fund_account_id')->nullable();
            $table->string('razorpay_virtual_account_id')->nullable(); // optional
            $table->string('verification_status')->default('pending'); // optional: pending, verified, failed
            $table->text('kyc_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_accounts');
    }
};

