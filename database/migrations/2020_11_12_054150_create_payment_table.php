<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment', function (Blueprint $table) {
            $table->bigincrements('payment_id')->comment('Primary key of table');
            $table->bigInteger('booking_id')->comment('Booking id');
            $table->date('date')->comment('date of payment');
            $table->bigInteger('transaction_id')->comment('transaction id');
            $table->text('response')->comment('response');
            $table->text('status')->comment('1=>faild 2=>faild,');
            $table->text('amount')->comment('payment amount');
            $table->bigInteger('cutomerid')->comment('user id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment');
    }
}
