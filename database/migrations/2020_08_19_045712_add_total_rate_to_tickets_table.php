<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalRateToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->text('ticket_number')->nullable()->comment('ticket number');
            $table->string('total_rate')->nullable()->comment('total rate of ticket');//
        });
        //DB::statement("ALTER TABLE tickets AUTO_INCREMENT = 14000;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::table('tickets', function (Blueprint $table) {
            //
        //});
    }
}
