<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsMailQueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_mail_que', function (Blueprint $table) {
            $table->bigincrements('smq_id')->comment('The primary key field of the table');
            $table->text('smq_to')->nullable()->comment('address of destination supperated by ","');
            $table->text('message')->nullable()->comment('message body');
            $table->Integer('sms_status')->default(1)->comment('The status of the message:1=>pending 2=> delivered, 3=>failed');
            $table->text('smq_recipient')->nullable()->comment('email address');
            $table->text('subject')->nullable()->comment('subject of mail');
            $table->Integer('mail_status')->default(1)->comment('The status of the message:1=>pending 2=> delivered, 3=>failed');
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
        Schema::dropIfExists('sms_mail_que');
    }
}
