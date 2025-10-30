<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->string('razorpay_contact_id', 100)->nullable()->index();
            $table->string('razorpay_fund_account_id', 100)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn(['razorpay_contact_id', 'razorpay_fund_account_id']);
        });
    }
};

