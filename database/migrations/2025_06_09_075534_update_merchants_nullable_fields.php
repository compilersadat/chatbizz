<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMerchantsNullableFields extends Migration
{
    public function up()
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->text('address')->nullable()->change();
            $table->text('lat')->nullable()->change();
            $table->text('lang')->nullable()->change();
            $table->text('thumbnail')->nullable()->change();
            $table->integer('status')->default(0)->nullable()->change();
            $table->foreignId('catagory_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->text('address')->nullable(false)->change();
            $table->text('lat')->nullable()->change();
            $table->text('lang')->nullable()->change();
            $table->text('thumbnail')->nullable(false)->change();
            $table->integer('status')->default(null)->nullable(false)->change();
            $table->foreignId('catagory_id')->nullable(false)->change();
        });
    }
};

