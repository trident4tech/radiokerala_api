<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVerificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('verification', function (Blueprint $table) {
            $table->bigincrements('verification_id')->comment('primary key of table');
            $table->text('verification_ticket_number')->nullable()->comment('ticket number');
            $table->text('verification_user_id')->nullable()->comment('user id');
            $table->text('verification_attr_id')->nullable()->comment('attraction id');
            $table->text('verification_dest_id')->nullable()->comment('destination id');
            $table->text('verification_total_number')->nullable()->comment('total number');
            $table->timestamp('verification_datetime')->nullable()->comment('date and time');
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
        Schema::dropIfExists('verification');
    }
}
