<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCountersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_counters', function (Blueprint $table) {
            $table->bigincrements('uc_id')->comment('The primary key field of the table');
            $table->bigInteger('uc_usr_id')->comment('foreign key for users');
            $table->bigInteger('uc_counter_id')->comment('foreign key for counters');            
            $table->foreign('uc_usr_id')->references('usr_id')->on('users');
            $table->foreign('uc_counter_id')->references('counter_id')->on('counter');             
            $table->smallInteger('status')->default(2)->comment('The status of the file: 2=> Enabled , 1=>Disabled');
            $table->bigInteger('u_createdby')->nullable()->comment('ID of the user who created this row');
            $table->bigInteger('u_modifiedby')->nullable()->comment('ID of the user who last updated this row');
            $table->bigInteger('u_deletedby')->nullable()->comment('ID of the user who deleted this row');
            $table->text('ip_created')->nullable()->comment('Created IP Address');
            $table->text('ip_modified')->nullable()->comment('Last modified IP Address');
            $table->text('ip_deleted')->nullable()->comment('Deleted IP Address');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_counters');
    }
}
