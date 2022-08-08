<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDestTypeToDestination extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('destination', function (Blueprint $table) {
            $table->Integer('dest_type')->default(1)->comment('Type of destination:1=>non-administrative 2=> administrative');     //
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
        Schema::table('destination', function (Blueprint $table) {
            //
        });
    }
}
