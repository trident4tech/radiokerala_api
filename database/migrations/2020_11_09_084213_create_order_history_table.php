<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_history', function (Blueprint $table) {
            $table->bigincrements("history_id")->comment('primary key of field');
            $table->integer("history_ticket_id")->comment('primary key of table tickets');
            $table->integer("history_user_id")->comment('primary key of table users');
            $table->integer("history_status")->comment('1=> payment success,2=>payment cancelled,3=>payment faild
                                                    ,4=>not verified,5=>verified,6=>completed');
            $table->dateTime('history_date')->comment(' date time last process');
            $table->integer("history_user_type")->comment('1=>counter users,2=>public users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_history');
    }
}
