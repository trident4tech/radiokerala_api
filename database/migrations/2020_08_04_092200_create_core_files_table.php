<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoreFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('core_files', function (Blueprint $table) {
           
            $table->bigincrements('file_id')->comment('The primary key field of the table');
            $table->text('file_name')->comment('Name of the file');
            $table->text('file_path')->comment('path of the user file');
            $table->text('file_type')->comment('type of the file');
            $table->smallInteger('file_error')->default(2)->comment('error field');
            $table->bigInteger('file_size')->comment('size of the file');
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
        Schema::dropIfExists('core_files');
    }
}
