<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDestNameToTicketClass extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_class', function (Blueprint $table) {
            $table->text('attraction_name')->nullable()->comment('attraction name');
            $table->text('class_name')->nullable()->comment('class name');
            $table->text('dest_name')->nullable()->comment('destination name');


            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_class', function (Blueprint $table) {
            //
        });
    }
}
