<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionLinkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permission_link', function (Blueprint $table) {
            $table->bigincrements('pl_id')->comment('Primary key of table');
            $table->bigInteger('pl_pwa_id')->comment('permission id of pwa');
            $table->bigInteger('pl_api_id')->comment('permission id of api');
            $table->foreign('pl_pwa_id')->references('id')->on('permissions');
            $table->foreign('pl_api_id')->references('id')->on('permissions');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permission_link');
    }
}
