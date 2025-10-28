<?php
// database/migrations/2025_10_28_120000_add_razorpay_ids_to_merchants.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('razorpay_contact_id')->nullable()->index();
            $table->string('razorpay_fund_account_id')->nullable()->index();

            // (optional) enforce uniqueness when present:
            // $table->unique('razorpay_contact_id');
            // $table->unique('razorpay_fund_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['razorpay_contact_id', 'razorpay_fund_account_id']);
        });
    }
};

