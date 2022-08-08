<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAclRootesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acl_rootes', function (Blueprint $table) {
            $table->bigincrements('acl_id')->comment('The primary key field of the table');
            $table->text('acl_rootes')->nullable()->comment('root link');
            $table->text('acl_name')->nullable()->comment('root name');
            $table->text('type')->default(1)->comment('type of roote');
            $table->text('category')->default(2)->comment('category = 1 <api root>,category = 2 <pwa root>');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('acl_rootes');
    }
}
