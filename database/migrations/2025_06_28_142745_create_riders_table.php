<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRidersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('riders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('mobile')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->tinyInteger('status')->default(1);      // 1 = Active, 0 = Inactive (you can adjust as needed)
            $table->tinyInteger('rstatus')->default(0);     // Rider status (you can adjust as needed)
            $table->decimal('rate', 8, 2)->nullable();      // Rider's rate, nullable
            $table->string('rimg')->nullable();             // Rider's image path, nullable
            $table->string('adhar_id')->nullable();         // Adhar ID, nullable
            $table->text('full_address')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('landmark')->nullable();
            $table->string('dzone')->nullable();            // Delivery zone, nullable
            $table->string('bank_name')->nullable();
            $table->string('ifsc')->nullable();
            $table->string('receipt_name')->nullable();     // Account holder's name, nullable
            $table->string('acc_number')->nullable();
            $table->string('upi_id')->nullable();
            $table->timestamps();                           // Adds created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('riders');
    }
}
