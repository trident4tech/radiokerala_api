<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->bigincrements('ticket_id')->comment('The primary key field of the table');
            $table->datetime('date')->nullable()->comment('date');
            $table->bigInteger('ticket_usr_id')->nullable()->comment('foreign key for users');
            $table->foreign('ticket_usr_id')->references('usr_id')->on('users');
            $table->bigInteger('ticket_counter_id')->nullable()->comment('foreign key for counter');
            $table->foreign('ticket_counter_id')->references('counter_id')->on('counter');
            $table->bigInteger('ticket_dest_id')->nullable()->comment('foreign key for destination');
            $table->foreign('ticket_dest_id')->references('dest_id')->on('destination');
            $table->text('customer_name')->nullable()->comment('customer name');
            $table->text('customer_mobile')->nullable()->comment('customer mobile');
            $table->text('customer_email')->nullable()->comment('customer email');
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
        Schema::dropIfExists('tickets');
    }
}
