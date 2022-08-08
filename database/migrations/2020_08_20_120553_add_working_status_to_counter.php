<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkingStatusToCounter extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('counter', function (Blueprint $table) {
            $table->text('opening_time')->nullable()->comment('opening time');
            $table->text('closing_time')->nullable()->comment('closing time');
            $table->smallInteger('working_status')->default(2)->comment('The status of the file: 2=> Enabled , 1=>Disabled');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('counter', function (Blueprint $table) {
            //
        });
    }
}
